<?php

namespace App\Services;

use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionLifecycleService
{
    public function __construct(
        private SellerSubscriptionService $visibility,
        private NotificationService $notifications,
    ) {}

    public function processPaidCheckout(WebhookEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $event = WebhookEvent::lockForUpdate()->findOrFail($event->id);
            if ($event->status === 'processed') return;

            $payload = $event->payload;
            $resource = data_get($payload, 'data.attributes.data', []);
            $attributes = $resource['attributes'] ?? [];
            $providerSessionId = $resource['id'] ?? data_get($attributes, 'checkout_session_id');
            $publicId = data_get($attributes, 'metadata.checkout_public_id')
                ?? data_get($attributes, 'reference_number');

            $checkout = CheckoutSession::query()
                ->when($providerSessionId, fn ($q) => $q->where('provider_session_id', $providerSessionId))
                ->when(! $providerSessionId && $publicId, fn ($q) => $q->where('public_id', $publicId))
                ->lockForUpdate()->first();

            if (! $checkout) throw new RuntimeException('No local checkout session matches this webhook.');
            if ($checkout->status === 'paid') {
                $event->update(['status' => 'processed', 'processed_at' => now()]);
                return;
            }

            $checkout->loadMissing('price.plan', 'user');
            $price = $checkout->price;
            $days = $price->days();
            $current = Subscription::where('user_id', $checkout->user_id)
                ->entitled()->lockForUpdate()->latest('current_period_end')->first();

            $start = now();
            $end = now()->addDays($days);
            if ($current && $current->seller_plan_id === $price->seller_plan_id) {
                $start = $current->current_period_start ?? now();
                $end = $current->current_period_end->copy()->addDays($days);
                $subscription = $current;
            } else {
                if ($current) $current->update(['status' => 'superseded', 'suspended_at' => now()]);
                $subscription = new Subscription(['user_id' => $checkout->user_id]);
            }

            $subscription->fill([
                'seller_plan_id' => $price->seller_plan_id,
                'seller_plan_price_id' => $price->id,
                'provider' => $checkout->provider,
                'status' => 'active',
                'current_period_start' => $start,
                'current_period_end' => $end,
                'grace_ends_at' => null,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'suspended_at' => null,
            ])->save();

            $paymentResource = $this->paymentResource($attributes, $resource);
            $providerPaymentId = $paymentResource['id'] ?? 'checkout:'.$checkout->provider_session_id;
            Payment::firstOrCreate(['provider_payment_id' => $providerPaymentId], [
                'user_id' => $checkout->user_id,
                'subscription_id' => $subscription->id,
                'checkout_session_id' => $checkout->id,
                'provider' => $checkout->provider,
                'plan_type' => "seller:{$price->plan->slug}:{$price->duration}",
                'amount' => $checkout->amount,
                'currency' => $checkout->currency,
                'status' => 'paid',
                'paid_at' => now(),
                'meta' => ['webhook_event_id' => $event->provider_event_id],
            ]);
            DB::table('payment_attempts')->updateOrInsert(
                ['provider_attempt_id' => $providerPaymentId],
                [
                    'subscription_id' => $subscription->id, 'checkout_session_id' => $checkout->id,
                    'provider' => $checkout->provider, 'status' => 'paid', 'amount' => $checkout->amount,
                    'currency' => $checkout->currency, 'metadata' => json_encode(['webhook_event_id' => $event->provider_event_id]),
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );

            $checkout->update(['status' => 'paid', 'subscription_id' => $subscription->id]);
            DB::table('billing_logs')->insert([
                'user_id' => $checkout->user_id, 'subscription_id' => $subscription->id,
                'event' => 'subscription.activated', 'actor_type' => 'webhook',
                'context' => json_encode(['checkout_session_id' => $checkout->id, 'provider_event_id' => $event->provider_event_id]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $event->update(['status' => 'processed', 'processed_at' => now(), 'error' => null]);

            $this->visibility->applyEntitlements($checkout->user);
            DB::afterCommit(fn () => $this->notifications->send(
                $checkout->user, 'subscription_paid', 'Subscription activated!',
                "Your {$price->plan->name} seller plan is active until {$end->format('M j, Y')}.",
                ['subscription_id' => $subscription->id], '/subscription'
            ));
        }, 3);
    }

    public function processFailedCheckout(WebhookEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $event = WebhookEvent::lockForUpdate()->findOrFail($event->id);
            if ($event->status === 'processed') return;
            $resource = data_get($event->payload, 'data.attributes.data', []);
            $attributes = $resource['attributes'] ?? [];
            $providerSessionId = $resource['id'] ?? data_get($attributes, 'checkout_session_id');
            $publicId = data_get($attributes, 'metadata.checkout_public_id') ?? data_get($attributes, 'reference_number');
            $checkout = CheckoutSession::query()
                ->when($providerSessionId, fn ($q) => $q->where('provider_session_id', $providerSessionId))
                ->when(! $providerSessionId && $publicId, fn ($q) => $q->where('public_id', $publicId))
                ->lockForUpdate()->first();
            if (! $checkout) throw new RuntimeException('No local checkout session matches this failed-payment webhook.');

            $code = (string) (data_get($attributes, 'last_payment_error.code') ?? 'payment_failed');
            $message = (string) (data_get($attributes, 'last_payment_error.message') ?? 'The payment was not completed.');
            $checkout->update(['status' => 'failed']);
            DB::table('payment_attempts')->insert([
                'checkout_session_id' => $checkout->id, 'provider' => $checkout->provider,
                'provider_attempt_id' => $event->provider_event_id, 'status' => 'failed',
                'amount' => $checkout->amount, 'currency' => $checkout->currency,
                'failure_code' => $code, 'failure_message' => $message,
                'metadata' => json_encode(['webhook_event_id' => $event->provider_event_id]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $event->update(['status' => 'processed', 'processed_at' => now(), 'error' => null]);
            DB::afterCommit(fn () => $this->notifications->send(
                $checkout->user, 'subscription_payment_failed', 'Payment was not completed',
                'Your seller plan was not changed. You can safely try checkout again.',
                ['checkout_session_id' => $checkout->public_id], '/subscription'
            ));
        }, 3);
    }

    private function paymentResource(array $attributes, array $resource): array
    {
        $payments = $attributes['payments'] ?? [];
        return is_array($payments) && isset($payments[0]) ? $payments[0] : $resource;
    }
}
