<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\CheckoutSession;
use App\Models\SellerPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        private PaymentGateway $gateway,
        private SellerSubscriptionService $visibility,
    ) {}

    public function checkout(User $user, SellerPlanPrice $price): CheckoutSession
    {
        $amount = (int) round(((float) $price->price) * 100);
        $session = CheckoutSession::create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'seller_plan_price_id' => $price->id,
            'provider' => config('billing.provider'),
            'idempotency_key' => 'checkout:'.Str::uuid(),
            'amount' => $amount,
            'currency' => config('billing.currency'),
            'expires_at' => now()->addMinutes(config('billing.checkout_ttl_minutes')),
        ]);

        try {
            $result = $this->gateway->createCheckout($session);
            $session->update([
                'provider_session_id' => $result['provider_session_id'],
                'checkout_url' => $result['checkout_url'],
                'expires_at' => $result['expires_at'] ?? $session->expires_at,
                'metadata' => ['provider_response' => $result['raw']],
            ]);
        } catch (\Throwable $e) {
            $session->update(['status' => 'failed', 'metadata' => ['error' => $e->getMessage()]]);
            throw $e;
        }

        return $session->fresh();
    }

    public function cancel(User $user, int $subscriptionId): void
    {
        DB::transaction(function () use ($user, $subscriptionId) {
            $subscription = $user->subscriptions()->lockForUpdate()->findOrFail($subscriptionId);
            if (! in_array($subscription->status, ['active', 'grace_period'], true)) {
                abort(422, 'Only an active subscription can be cancelled.');
            }
            $subscription->update(['cancel_at_period_end' => true, 'cancelled_at' => now()]);
            DB::table('billing_logs')->insert([
                'user_id' => $user->id, 'subscription_id' => $subscription->id,
                'event' => 'subscription.cancellation_scheduled', 'actor_type' => 'user',
                'actor_id' => $user->id, 'context' => json_encode([]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function refund(Payment $payment, User $admin, int $amount, string $reason): Refund
    {
        abort_unless($payment->provider === 'paymongo' && $payment->status === 'paid', 422, 'Only paid PayMongo payments can be refunded here.');
        abort_if($amount < 1 || $amount > $payment->amount, 422, 'Invalid refund amount.');
        $result = $this->gateway->refund($payment, $amount, $reason);

        return DB::transaction(function () use ($payment, $admin, $amount, $reason, $result) {
            $refund = Refund::create([
                'payment_id' => $payment->id, 'requested_by' => $admin->id, 'provider' => $payment->provider,
                'provider_refund_id' => $result['provider_refund_id'], 'amount' => $amount,
                'currency' => $payment->currency, 'reason' => $reason, 'status' => $result['status'],
                'metadata' => ['provider_response' => $result['raw']],
            ]);

            // Mark the payment refunded so it (a) drops out of revenue sums,
            // (b) can't be refunded a second time — abort_unless above only
            // allows status==='paid'.
            $isFullRefund = $amount >= $payment->amount;
            $payment->update([
                'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'refunded_at' => now(),
            ]);

            // A full refund ends access immediately — no grace period. This
            // mirrors the confirmed policy for manual-GCash refunds: refund
            // and access end together, not at the end of the paid period.
            if ($isFullRefund && $payment->subscription_id) {
                $subscription = Subscription::lockForUpdate()->find($payment->subscription_id);
                if ($subscription && in_array($subscription->status, Subscription::ACTIVE_STATUSES, true)) {
                    $subscription->update([
                        'status' => 'refunded',
                        'current_period_end' => now(),
                        'grace_ends_at' => null,
                        'cancel_at_period_end' => false,
                    ]);
                    $this->visibility->applyEntitlements($subscription->user);
                }
            }

            DB::table('billing_logs')->insert(['user_id' => $payment->user_id, 'subscription_id' => $payment->subscription_id,
                'event' => 'refund.requested', 'actor_type' => 'admin', 'actor_id' => $admin->id,
                'context' => json_encode(['refund_id' => $refund->id, 'amount' => $amount]), 'created_at' => now(), 'updated_at' => now()]);
            return $refund;
        });
    }
}
