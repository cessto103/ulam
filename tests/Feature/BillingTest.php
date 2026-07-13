<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\CheckoutSession;
use App\Models\SellerPlan;
use App\Models\SellerPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\PayMongoGateway;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private SellerPlanPrice $price;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        SellerPlan::create(['slug' => 'free', 'name' => 'Free', 'max_stores' => 1, 'max_items_per_store' => 10]);
        $plan = SellerPlan::create(['slug' => 'basic', 'name' => 'Basic', 'max_stores' => 2, 'max_items_per_store' => 30]);
        $this->price = SellerPlanPrice::create(['seller_plan_id' => $plan->id, 'duration' => '1m', 'price' => 99, 'is_active' => true]);
    }

    public function test_checkout_uses_server_price_and_returns_hosted_url(): void
    {
        $this->app->instance(PaymentGateway::class, new class implements PaymentGateway {
            public function createCheckout(CheckoutSession $session): array
            {
                return ['provider_session_id' => 'cs_test_1', 'checkout_url' => 'https://checkout.paymongo.test/1', 'expires_at' => null, 'raw' => []];
            }
            public function verifyWebhook(string $rawBody, string $signatureHeader): bool { return true; }
            public function refund(\App\Models\Payment $payment, int $amount, string $reason): array { return ['provider_refund_id' => 'ref_1', 'status' => 'pending', 'raw' => []]; }
        });
        Sanctum::actingAs($this->user);

        $this->postJson('/api/billing/checkout', ['price_id' => $this->price->id, 'amount' => 1])
            ->assertCreated()->assertJsonPath('checkout_url', 'https://checkout.paymongo.test/1');

        $this->assertDatabaseHas('checkout_sessions', ['user_id' => $this->user->id, 'amount' => 9900, 'provider_session_id' => 'cs_test_1']);
    }

    public function test_free_plan_cannot_be_purchased(): void
    {
        $free = SellerPlan::where('slug', 'free')->first();
        $price = SellerPlanPrice::create(['seller_plan_id' => $free->id, 'duration' => '1m', 'price' => 1, 'is_active' => true]);
        Sanctum::actingAs($this->user);
        $this->postJson('/api/billing/checkout', ['price_id' => $price->id])->assertStatus(422);
    }

    public function test_paid_webhook_activation_is_idempotent(): void
    {
        $checkout = CheckoutSession::create([
            'public_id' => fake()->uuid(), 'user_id' => $this->user->id,
            'seller_plan_price_id' => $this->price->id, 'provider' => 'paymongo',
            'provider_session_id' => 'cs_paid', 'idempotency_key' => fake()->uuid(),
            'status' => 'pending', 'amount' => 9900, 'currency' => 'PHP',
        ]);
        $payload = ['data' => ['id' => 'evt_1', 'attributes' => [
            'type' => 'checkout_session.payment.paid', 'livemode' => false,
            'data' => ['id' => 'cs_paid', 'attributes' => ['payments' => [['id' => 'pay_1']]]],
        ]]];
        $event = WebhookEvent::create([
            'provider' => 'paymongo', 'provider_event_id' => 'evt_1',
            'event_type' => 'checkout_session.payment.paid', 'payload' => $payload,
        ]);

        $service = app(SubscriptionLifecycleService::class);
        $service->processPaidCheckout($event);
        $service->processPaidCheckout($event->fresh());

        $this->assertSame(1, Subscription::count());
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('checkout_sessions', ['id' => $checkout->id, 'status' => 'paid']);
        $this->assertDatabaseHas('webhook_events', ['id' => $event->id, 'status' => 'processed']);
    }

    public function test_webhook_signature_rejects_tampering_and_stale_timestamps(): void
    {
        config(['services.paymongo.webhook_secret' => 'secret']);
        $body = json_encode(['data' => ['attributes' => ['livemode' => false]]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'secret');
        $gateway = app(PayMongoGateway::class);

        $this->assertTrue($gateway->verifyWebhook($body, "t={$timestamp},te={$signature}"));
        $this->assertFalse($gateway->verifyWebhook($body.' ', "t={$timestamp},te={$signature}"));
        $this->assertFalse($gateway->verifyWebhook($body, 't='.($timestamp - 301).",te={$signature}"));
    }

    public function test_users_can_only_cancel_their_own_subscription(): void
    {
        $other = User::factory()->create();
        $subscription = Subscription::create([
            'user_id' => $other->id, 'seller_plan_id' => $this->price->seller_plan_id,
            'seller_plan_price_id' => $this->price->id, 'status' => 'active',
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        Sanctum::actingAs($this->user);
        $this->postJson("/api/billing/subscriptions/{$subscription->id}/cancel")->assertNotFound();
    }
}
