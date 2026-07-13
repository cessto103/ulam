<?php

namespace Database\Seeders;

use App\Models\AdBoost;
use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\Recipe;
use App\Models\Refund;
use App\Models\SellerPlan;
use App\Models\SellerPlanPrice;
use App\Models\Subscription;
use App\Models\Tindahan;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data for the PayMongo billing platform + boosters, so every state the
 * admin dashboards and app can show has a real row behind it: active,
 * grace period, expired, superseded (plan switch), cancel-at-period-end,
 * refunded, and a failed checkout. Not run by DatabaseSeeder — run on demand:
 *   php artisan db:seed --class=BillingSimulationSeeder
 *
 * Idempotent: every provider id is derived deterministically from a scenario
 * tag (not random), and child rows (payment, webhook event, log) are only
 * created the first time a scenario's Subscription row is created — re-runs
 * find the same rows and leave them alone instead of piling up duplicates.
 */
class BillingSimulationSeeder extends Seeder
{
    public function run(): void
    {
        $planBySlug = SellerPlan::with('prices')->get()->keyBy('slug');
        if ($planBySlug->count() < 4) {
            $this->command->error('Seller plans are missing — run SellerPlanSeeder first.');
            return;
        }

        $priceFor = fn (string $slug, string $duration) => $planBySlug[$slug]->prices
            ->firstWhere('duration', $duration);

        $users = User::whereIn('email', [
            'cessto103@gmail.com',
            'maria.demo@ulam.app',
            'lito.demo@ulam.app',
            'ana.demo@ulam.app',
            'eddie.demo@ulam.app',
            'cora.demo@ulam.app',
        ])->get()->keyBy('email');

        if ($users->count() < 6) {
            $this->command->error('Demo users are missing — run UserSeeder/DemoStoreSeeder first.');
            return;
        }

        // 1) cessto103 — active Suki, monthly, paid ~15 days ago.
        $this->paidSubscription(
            tag: 'cessto-suki-active',
            user: $users['cessto103@gmail.com'],
            price: $priceFor('suki', '1m'),
            periodStart: now()->subDays(15),
            days: 30,
            status: 'active',
        );

        // 2) Maria — superseded Basic (she upgraded), then current Suki now
        //    lapsed into its grace period.
        $old = $this->paidSubscription(
            tag: 'maria-basic-superseded',
            user: $users['maria.demo@ulam.app'],
            price: $priceFor('basic', '1m'),
            periodStart: now()->subDays(60),
            days: 30,
            status: 'superseded',
        );
        $grace = $this->paidSubscription(
            tag: 'maria-suki-grace',
            user: $users['maria.demo@ulam.app'],
            price: $priceFor('suki', '1m'),
            periodStart: now()->subDays(32),
            days: 30,
            status: 'grace_period',
            note: 'plan switch — supersedes subscription #' . $old->id,
        );
        $grace->update(['grace_ends_at' => now()->addDay()]);

        // 3) Lito Flores — fully expired Negosyante, yearly (lapsed well past
        //    grace; nothing currently runs the expiry sweep, so this state
        //    has to be seeded directly rather than produced by a webhook).
        $this->paidSubscription(
            tag: 'lito-negosyante-expired',
            user: $users['lito.demo@ulam.app'],
            price: $priceFor('negosyante', '1y'),
            periodStart: now()->subDays(400),
            days: 365,
            status: 'expired',
        );

        // 4) Ana — active Basic (7d), cancelled but still entitled until
        //    period end (cancel_at_period_end=true, matches BillingService::cancel()).
        $cancelling = $this->paidSubscription(
            tag: 'ana-basic-cancelling',
            user: $users['ana.demo@ulam.app'],
            price: $priceFor('basic', '7d'),
            periodStart: now()->subDays(3),
            days: 7,
            status: 'active',
        );
        if (! $cancelling->cancel_at_period_end) {
            $cancelling->update(['cancel_at_period_end' => true, 'cancelled_at' => now()->subDay()]);
            $this->log('subscription.cancellation_scheduled', $cancelling, 'user', $cancelling->user_id, now()->subDay());
        }

        // 5) Mang Eddie — a failed checkout. Mirrors
        //    SubscriptionLifecycleService::processFailedCheckout() exactly:
        //    a failed checkout session + a payment_attempts row, no Payment,
        //    no Subscription.
        $this->failedCheckout('eddie-suki-failed', $users['eddie.demo@ulam.app'], $priceFor('suki', '1m'));

        // 6) Ate Cora — paid Suki monthly, then fully refunded by an admin.
        //    Mirrors the fixed BillingService::refund(): payment marked
        //    refunded AND the subscription ends immediately (no grace).
        $refundSub = $this->paidSubscription(
            tag: 'cora-suki-refunded',
            user: $users['cora.demo@ulam.app'],
            price: $priceFor('suki', '1m'),
            periodStart: now()->subDays(5),
            days: 30,
            status: 'active',
        );
        if ($refundSub->status !== 'refunded') {
            $this->refundSubscription('cora-suki-refunded', $refundSub, $users['cessto103@gmail.com']);
        }

        $this->command->info('BillingSimulationSeeder: subscriptions, payments, checkout sessions, webhook events, and a refund seeded.');

        $this->seedBoosts($users);

        $this->command->info('BillingSimulationSeeder: boosts seeded. Done.');
    }

    /**
     * Creates the full chain a successful PayMongo webhook would have
     * produced: CheckoutSession(paid) -> Subscription -> Payment ->
     * payment_attempts -> billing_logs -> WebhookEvent(processed).
     * All provider ids are deterministic hashes of $tag, so a second run
     * matches the same rows instead of inserting duplicates.
     */
    private function paidSubscription(
        string $tag,
        User $user,
        SellerPlanPrice $price,
        Carbon $periodStart,
        int $days,
        string $status,
        ?string $note = null,
    ): Subscription {
        $providerSessionId = 'cs_demo_' . substr(md5($tag), 0, 20);

        // The checkout session's provider id is the only truly stable key
        // here (Subscription has no external reference of its own, and its
        // status is deliberately mutated post-creation by some scenarios —
        // e.g. the refund case — so keying on business fields would create a
        // fresh duplicate every time a re-run saw the mutated status). Once
        // this scenario has been seeded once, leave it alone entirely.
        $existingCheckout = CheckoutSession::where('provider_session_id', $providerSessionId)->first();
        if ($existingCheckout?->subscription_id) {
            return Subscription::findOrFail($existingCheckout->subscription_id);
        }

        return DB::transaction(function () use ($tag, $user, $price, $periodStart, $days, $status, $note, $providerSessionId) {
            $amount = (int) round(((float) $price->price) * 100);
            $providerPaymentId = 'pay_demo_' . substr(md5($tag . ':payment'), 0, 20);
            $providerEventId = 'evt_demo_' . substr(md5($tag . ':paid'), 0, 20);

            // created_at/updated_at aren't in these models' $fillable, so a
            // plain create() would silently drop the backdated timestamps and
            // stamp "now" instead — forceFill() is needed to make the seeded
            // history actually look historical.
            $subscription = new Subscription();
            $subscription->forceFill([
                'user_id' => $user->id,
                'seller_plan_id' => $price->seller_plan_id,
                'seller_plan_price_id' => $price->id,
                'provider' => 'paymongo',
                'status' => $status,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodStart->copy()->addDays($days),
                'created_at' => $periodStart,
                'updated_at' => $periodStart,
            ])->save();

            $checkout = new CheckoutSession();
            $checkout->forceFill([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'seller_plan_price_id' => $price->id,
                'subscription_id' => $subscription->id,
                'provider' => 'paymongo',
                'provider_session_id' => $providerSessionId,
                'idempotency_key' => 'checkout:' . $providerSessionId,
                'status' => 'paid',
                'amount' => $amount,
                'currency' => 'PHP',
                'checkout_url' => 'https://checkout.paymongo.com/demo/' . $providerSessionId,
                'expires_at' => $periodStart->copy()->addHour(),
                'metadata' => ['seeded' => true],
                'created_at' => $periodStart,
                'updated_at' => $periodStart,
            ])->save();

            $payment = new Payment();
            $payment->forceFill([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'checkout_session_id' => $checkout->id,
                'provider' => 'paymongo',
                'provider_payment_id' => $providerPaymentId,
                'plan_type' => "seller:{$price->plan->slug}:{$price->duration}",
                'amount' => $amount,
                'currency' => 'PHP',
                'status' => 'paid',
                'paid_at' => $periodStart,
                'meta' => ['seeded' => true],
                'created_at' => $periodStart,
                'updated_at' => $periodStart,
            ])->save();

            DB::table('payment_attempts')->insert([
                'subscription_id' => $subscription->id, 'checkout_session_id' => $checkout->id,
                'provider' => 'paymongo', 'provider_attempt_id' => $providerPaymentId, 'status' => 'paid',
                'amount' => $amount, 'currency' => 'PHP',
                'metadata' => json_encode(['seeded' => true]),
                'created_at' => $periodStart, 'updated_at' => $periodStart,
            ]);

            $this->log('subscription.activated', $subscription, 'webhook', null, $periodStart, $note);

            $webhookEvent = new WebhookEvent();
            $webhookEvent->forceFill([
                'provider' => 'paymongo', 'provider_event_id' => $providerEventId,
                'event_type' => 'checkout_session.payment.paid',
                'livemode' => false, 'status' => 'processed',
                'payload' => ['seeded' => true, 'checkout_session_id' => $providerSessionId],
                'processed_at' => $periodStart,
                'created_at' => $periodStart, 'updated_at' => $periodStart,
            ])->save();

            return $subscription;
        });
    }

    /** Mirrors SubscriptionLifecycleService::processFailedCheckout() exactly. */
    private function failedCheckout(string $tag, User $user, SellerPlanPrice $price): void
    {
        $providerSessionId = 'cs_demo_' . substr(md5($tag), 0, 20);
        $providerEventId = 'evt_demo_' . substr(md5($tag . ':failed'), 0, 20);
        $when = now()->subHours(6);

        $existing = WebhookEvent::where('provider_event_id', $providerEventId)->exists();
        if ($existing) {
            return;
        }

        $amount = (int) round(((float) $price->price) * 100);

        DB::transaction(function () use ($user, $price, $amount, $when, $providerSessionId, $providerEventId) {
            $checkout = CheckoutSession::updateOrCreate(
                ['provider_session_id' => $providerSessionId],
                [
                    'public_id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'seller_plan_price_id' => $price->id,
                    'provider' => 'paymongo',
                    'idempotency_key' => 'checkout:' . $providerSessionId,
                    'status' => 'failed',
                    'amount' => $amount,
                    'currency' => 'PHP',
                    'checkout_url' => null,
                    'expires_at' => $when->copy()->addHour(),
                    'metadata' => ['seeded' => true],
                ]
            );
            $checkout->forceFill(['created_at' => $when, 'updated_at' => $when])->save();

            DB::table('payment_attempts')->updateOrInsert(
                ['provider_attempt_id' => $providerEventId],
                [
                    'checkout_session_id' => $checkout->id, 'provider' => 'paymongo', 'status' => 'failed',
                    'amount' => $amount, 'currency' => 'PHP',
                    'failure_code' => 'insufficient_funds',
                    'failure_message' => 'The GCash account did not have enough balance.',
                    'metadata' => json_encode(['seeded' => true]),
                    'created_at' => $when, 'updated_at' => $when,
                ]
            );

            WebhookEvent::updateOrCreate(
                ['provider_event_id' => $providerEventId],
                [
                    'provider' => 'paymongo', 'event_type' => 'checkout_session.payment.failed',
                    'livemode' => false, 'status' => 'processed',
                    'payload' => ['seeded' => true, 'checkout_session_id' => $providerSessionId],
                    'processed_at' => $when,
                ]
            )->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        });
    }

    /** Mirrors the (fixed) BillingService::refund(): payment refunded + subscription ended immediately. */
    private function refundSubscription(string $tag, Subscription $subscription, User $admin): void
    {
        $payment = Payment::where('subscription_id', $subscription->id)->firstOrFail();
        $providerRefundId = 'ref_demo_' . substr(md5($tag . ':refund'), 0, 20);

        $refund = Refund::updateOrCreate(
            ['provider_refund_id' => $providerRefundId],
            [
                'payment_id' => $payment->id,
                'requested_by' => $admin->id,
                'provider' => 'paymongo',
                'amount' => $payment->amount,
                'currency' => 'PHP',
                'reason' => 'requested_by_customer',
                'status' => 'succeeded',
                'metadata' => ['seeded' => true],
                'processed_at' => now(),
            ]
        );

        $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
        $subscription->update([
            'status' => 'refunded',
            'current_period_end' => now(),
            'grace_ends_at' => null,
            'cancel_at_period_end' => false,
        ]);

        $this->log('refund.requested', $subscription, 'admin', $admin->id, now(), null, [
            'refund_id' => $refund->id,
            'amount' => $payment->amount,
        ]);
    }

    private function log(
        string $event,
        Subscription $subscription,
        string $actorType,
        ?int $actorId,
        Carbon $when,
        ?string $note = null,
        array $extraContext = [],
    ): void {
        $context = array_filter(array_merge(['seeded' => true, 'note' => $note], $extraContext));

        DB::table('billing_logs')->insert([
            'user_id' => $subscription->user_id, 'subscription_id' => $subscription->id,
            'event' => $event, 'actor_type' => $actorType, 'actor_id' => $actorId,
            'context' => json_encode($context),
            'created_at' => $when, 'updated_at' => $when,
        ]);
    }

    /** @param \Illuminate\Support\Collection<string, User> $users */
    private function seedBoosts($users): void
    {
        $itoyStore = Tindahan::where('name', 'ITOY Sari Sari Store')->first();
        $eddieStore = Tindahan::where('name', "Mang Eddie's Meat Shop")->first();
        $recipe = Recipe::first();

        if (! $itoyStore || ! $eddieStore || ! $recipe) {
            $this->command->warn('Skipping boosts — expected demo store/recipe rows not found.');
            return;
        }

        // Active store boost (7 days, started 2 days ago).
        AdBoost::updateOrCreate(
            [
                'user_id' => $users['cessto103@gmail.com']->id,
                'boostable_type' => Tindahan::class,
                'boostable_id' => $itoyStore->id,
                'status' => 'active',
            ],
            [
                'duration' => '7_day',
                'amount_paid' => 79.00,
                'starts_at' => now()->subDays(2),
                'expires_at' => now()->addDays(5),
            ]
        );

        // Expired store boost (already ran its course).
        AdBoost::updateOrCreate(
            [
                'user_id' => $users['eddie.demo@ulam.app']->id,
                'boostable_type' => Tindahan::class,
                'boostable_id' => $eddieStore->id,
                'status' => 'expired',
            ],
            [
                'duration' => '3_day',
                'amount_paid' => 39.00,
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->subDays(7),
            ]
        );

        // Pending recipe boost (payment submitted, not yet activated).
        AdBoost::updateOrCreate(
            [
                'user_id' => $users['maria.demo@ulam.app']->id,
                'boostable_type' => Recipe::class,
                'boostable_id' => $recipe->id,
                'status' => 'pending',
            ],
            [
                'duration' => '3_day',
                'amount_paid' => 29.00,
                'starts_at' => null,
                'expires_at' => null,
            ]
        );
    }
}
