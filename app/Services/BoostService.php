<?php

namespace App\Services;

use App\Models\AdBoost;
use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserRewardTier;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BoostService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Activates a boost immediately by spending an already-earned Reward
     * Tier credit -- no payment reference, no admin review, since the
     * "payment" already happened the moment the tier was earned.
     *
     * Re-checks and locks the credit row inside the transaction (not just
     * the caller's earlier lookup) so two concurrent requests for the same
     * credit can't both pass the "not yet redeemed" check and both create
     * a free boost.
     */
    public function activateFromCredit(Model $boostable, string $modelClass, int $creditId, string $expectedRewardType, User $user): AdBoost
    {
        return DB::transaction(function () use ($boostable, $modelClass, $creditId, $expectedRewardType, $user) {
            $credit = UserRewardTier::where('user_id', $user->id)
                ->where('id', $creditId)
                ->whereNull('redeemed_at')
                ->with('rewardTier')
                ->lockForUpdate()
                ->first();

            if (! $credit) {
                throw new \RuntimeException('That boost credit is not available.');
            }

            if ($credit->rewardTier->reward_type !== $expectedRewardType) {
                throw new \RuntimeException('This credit cannot be used for that target.');
            }

            $durationDays = $credit->rewardTier->reward_value;
            $expiresAt = now()->addDays($durationDays);

            $boost = AdBoost::create([
                'user_id' => $user->id,
                'boostable_type' => $modelClass,
                'boostable_id' => $boostable->id,
                'duration_days' => $durationDays,
                'amount_paid' => 0,
                'payment_method' => 'reward_credit',
                'payment_reference' => null,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'reviewed_at' => now(),
            ]);

            Payment::create([
                'user_id' => $user->id,
                'provider' => 'reward_credit',
                'provider_payment_id' => "reward-credit-{$credit->id}",
                'plan_type' => "boost:{$modelClass}:{$durationDays}d",
                'amount' => 0,
                'currency' => 'PHP',
                'status' => 'paid',
                'paid_at' => now(),
                'meta' => ['ad_boost_id' => $boost->id, 'user_reward_tier_id' => $credit->id],
            ]);

            $credit->update(['redeemed_at' => now()]);

            $this->notifications->send(
                $user,
                'boost',
                'Boost activated! 🚀',
                "Your boost is now active until {$expiresAt->format('M j, Y')}. Enjoy the extra visibility!",
                ['ad_boost_id' => $boost->id],
                $modelClass === \App\Models\Recipe::class ? "/recipe/{$boost->boostable_id}" : "/stall/{$boost->boostable_id}",
            );

            return $boost->fresh();
        });
    }

    /**
     * Activates a boost once PayMongo's webhook confirms payment on its
     * CheckoutSession — the automated counterpart to activateFromCredit(),
     * replacing the old manual-GCash-reference + admin-approval flow.
     */
    public function activateFromCheckout(CheckoutSession $checkout, WebhookEvent $event): AdBoost
    {
        $checkout->loadMissing('boostOption', 'boostable', 'user');
        $option = $checkout->boostOption;
        $expiresAt = now()->addDays($option->duration_days);

        $boost = AdBoost::create([
            'user_id' => $checkout->user_id,
            'boostable_type' => $checkout->boostable_type,
            'boostable_id' => $checkout->boostable_id,
            'duration_days' => $option->duration_days,
            'amount_paid' => $option->price,
            'payment_method' => 'paymongo',
            'payment_reference' => $checkout->provider_session_id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'reviewed_at' => now(),
        ]);

        Payment::firstOrCreate(['checkout_session_id' => $checkout->id], [
            'user_id' => $checkout->user_id,
            'checkout_session_id' => $checkout->id,
            'provider' => $checkout->provider,
            'provider_payment_id' => 'checkout:'.$checkout->provider_session_id,
            'plan_type' => "boost:{$checkout->boostable_type}:{$option->duration_days}d",
            'amount' => $checkout->amount,
            'currency' => $checkout->currency,
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => ['ad_boost_id' => $boost->id, 'webhook_event_id' => $event->provider_event_id],
        ]);

        $checkout->update(['status' => 'paid']);
        $event->update(['status' => 'processed', 'processed_at' => now(), 'error' => null]);

        $this->notifications->send(
            $checkout->user,
            'boost',
            'Boost activated! 🚀',
            "Your boost is now active until {$expiresAt->format('M j, Y')}. Enjoy the extra visibility!",
            ['ad_boost_id' => $boost->id],
            $checkout->boostable_type === \App\Models\Recipe::class ? "/recipe/{$boost->boostable_id}" : "/stall/{$boost->boostable_id}",
        );

        return $boost->fresh();
    }
}
