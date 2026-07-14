<?php

namespace App\Services;

use App\Models\User;

class PremiumTrialService
{
    /** Streak length => trial days granted the day the streak first reaches it. */
    private const MILESTONES = [
        3 => 3,
        7 => 7,
    ];

    public function __construct(private NotificationService $notifications)
    {
    }

    public function grantForStreak(User $user, int $streakDays): void
    {
        $trialDays = self::MILESTONES[$streakDays] ?? null;
        if (! $trialDays) {
            return;
        }

        $newExpiry = now()->addDays($trialDays);
        $current   = $user->premium_expires_at;

        // Extend-only: never shorten a real paid subscriber's remaining time.
        if ($current && $current->isFuture() && $current->greaterThanOrEqualTo($newExpiry)) {
            return;
        }

        $user->update([
            'plan'                => 'premium',
            'premium_expires_at'  => $newExpiry,
            'premium_source'      => 'trial',
        ]);

        $this->notifications->send(
            $user,
            'premium_trial',
            '🎁 Free Premium unlocked!',
            "{$trialDays}-day free uLam Premium unlocked for your {$streakDays}-day streak!",
            ['trial_days' => $trialDays, 'streak_days' => $streakDays],
            '/upgrade',
        );
    }
}
