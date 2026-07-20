<?php

namespace App\Services;

use App\Models\AdBoost;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserRewardTier;
use Illuminate\Database\Eloquent\Model;

class BoostService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Activates a boost immediately by spending an already-earned Reward
     * Tier credit -- no payment reference, no admin review, since the
     * "payment" already happened the moment the tier was earned.
     */
    public function activateFromCredit(Model $boostable, string $modelClass, UserRewardTier $credit, User $user): AdBoost
    {
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
    }

    public function approve(AdBoost $boost, User $admin): AdBoost
    {
        $startsAt = now();
        $expiresAt = now()->addDays($boost->duration_days ?? 7);

        $boost->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'reviewed_at' => now(),
            'activated_by' => $admin->id,
        ]);

        Payment::create([
            'user_id' => $boost->user_id,
            'provider' => $boost->payment_method,
            'provider_payment_id' => $boost->payment_reference,
            'plan_type' => "boost:{$boost->boostable_type}:{$boost->duration_days}d",
            'amount' => (int) round($boost->amount_paid * 100),
            'currency' => 'PHP',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => ['ad_boost_id' => $boost->id],
        ]);

        $this->notifications->send(
            $boost->user,
            'boost',
            'Boost activated! 🚀',
            "Your boost is now active until {$expiresAt->format('M j, Y')}. Enjoy the extra visibility!",
            ['ad_boost_id' => $boost->id],
            $boost->boostable_type === \App\Models\Recipe::class ? "/recipe/{$boost->boostable_id}" : "/stall/{$boost->boostable_id}",
        );

        return $boost->fresh();
    }

    public function reject(AdBoost $boost, User $admin, string $reason): AdBoost
    {
        $boost->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'reviewed_at' => now(),
            'activated_by' => $admin->id,
        ]);

        $this->notifications->send(
            $boost->user,
            'boost',
            'Boost payment could not be verified',
            "Your boost payment was declined: {$reason}. Please double-check the reference number and try again.",
            ['ad_boost_id' => $boost->id],
            null,
        );

        return $boost->fresh();
    }
}
