<?php

namespace App\Services;

use App\Models\RewardTier;
use App\Models\User;
use App\Models\UserRewardTier;
use App\Models\UserTask;

class RewardTierService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Checks every active, not-yet-earned tier against the user's current
     * state. Called from XpService::award() after task completions have
     * already been recorded and the user's XP is up to date, so an
     * xp_threshold check here always sees this award cycle's final value.
     *
     * @return array<int,array{id:int,name:string,icon:?string,xp_reward:int,tier:?string,frequency:string,kind:string,reward_type:string}>
     */
    public function checkRewardTiers(User $user): array
    {
        $tiers = RewardTier::where('is_active', true)
            ->whereDoesntHave('userRewardTiers', fn ($q) => $q->where('user_id', $user->id))
            ->with('requiredTasks')
            ->get();

        $newlyEarned = [];

        foreach ($tiers as $tier) {
            if (! $this->isSatisfied($tier, $user)) {
                continue;
            }

            $userRewardTier = UserRewardTier::create([
                'user_id' => $user->id,
                'reward_tier_id' => $tier->id,
                'earned_at' => now(),
            ]);

            $this->fulfill($tier, $userRewardTier, $user);

            $this->notifications->send(
                $user,
                'reward_tier_earned',
                "🎁 {$tier->title}!",
                "You unlocked \"{$tier->title}\"!",
                ['reward_tier_id' => $tier->id],
                '/(tabs)/awards',
            );

            $newlyEarned[] = [
                'id' => $tier->id,
                'name' => $tier->title,
                'icon' => $tier->icon,
                'xp_reward' => 0,
                'tier' => null,
                'frequency' => 'once',
                'kind' => 'reward_tier',
                'reward_type' => $tier->reward_type,
            ];
        }

        return $newlyEarned;
    }

    private function isSatisfied(RewardTier $tier, User $user): bool
    {
        if ($tier->xp_threshold !== null && $user->xp < $tier->xp_threshold) {
            return false;
        }

        $requiredIds = $tier->requiredTasks->pluck('id');

        if ($requiredIds->isEmpty()) {
            // A tier with no task gate must have an xp_threshold or it's
            // unearnable by design -- write-time validation (admin controller)
            // is the real prevention, this is just a defensive runtime guard.
            return $tier->xp_threshold !== null;
        }

        $completed = UserTask::where('user_id', $user->id)
            ->whereIn('task_id', $requiredIds)
            ->where('is_completed', true)
            ->distinct('task_id')
            ->count('task_id');

        return $completed >= $requiredIds->count();
    }

    private function fulfill(RewardTier $tier, UserRewardTier $userRewardTier, User $user): void
    {
        match ($tier->reward_type) {
            'premium_days' => $this->grantPremiumDays($user, $tier->reward_value, $userRewardTier),
            'badge' => $userRewardTier->update(['redeemed_at' => now()]),
            // booster_credit / store_boost_credit stay unredeemed -- spent
            // later through BoostController's credit-redemption path.
            // discount_code isn't implemented; not selectable from admin.
            default => null,
        };
    }

    private function grantPremiumDays(User $user, int $days, UserRewardTier $userRewardTier): void
    {
        $newExpiry = now()->addDays($days);
        $current = $user->premium_expires_at;

        // Extend-only: never shorten a subscriber's remaining time. Same
        // rule PremiumTrialService::grantForStreak() applies for streak
        // trials -- replicated here rather than reused, since that
        // service's notification copy is streak-specific.
        if (! ($current && $current->isFuture() && $current->greaterThanOrEqualTo($newExpiry))) {
            $user->update([
                'plan' => 'premium',
                'premium_expires_at' => $newExpiry,
                'premium_source' => 'reward',
            ]);
        }

        $userRewardTier->update(['redeemed_at' => now()]);
    }
}
