<?php

namespace App\Services;

use App\Models\ContentReport;
use App\Models\ListingReport;
use App\Models\User;
use App\Models\UserStrike;

/**
 * Single place that owns "what happens to a user's account state when
 * they're warned/restricted/banned." Both ListingReportController::banOwner()
 * and Admin\UserController::ban()/unban() call into this instead of each
 * mutating the User row independently -- previously banOwner() didn't revoke
 * Sanctum tokens the way the "canonical" ban endpoint did, and neither path
 * left any strike history.
 */
class UserModerationService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function warn(User $user, string $reason, User $admin, ?ContentReport $contentReport = null, ?ListingReport $listingReport = null): UserStrike
    {
        $strike = $this->createStrike($user, UserStrike::LEVEL_WARNING, $reason, $admin, $contentReport, $listingReport, $this->strikeExpiry());

        // $reason is the admin-entered violation category/description only --
        // never the reporter's identity. Reporter identity must never reach
        // the reported user; only admins ever see who filed a report.
        $this->notifications->send(
            $user,
            'moderation_warning',
            '⚠️ Community guidelines warning',
            "You've received a warning: {$reason}. Please review our community guidelines to avoid further action on your account.",
            ['strike_id' => $strike->id],
            '/account-status',
        );

        return $strike;
    }

    public function restrict(User $user, string $reason, User $admin, ?ContentReport $contentReport = null, ?ListingReport $listingReport = null, ?int $days = null): UserStrike
    {
        $days ??= config('moderation.restriction_days');
        $restrictedUntil = now()->addDays($days);

        $strike = $this->createStrike($user, UserStrike::LEVEL_RESTRICTION, $reason, $admin, $contentReport, $listingReport, $this->strikeExpiry());

        $user->update(['restricted_until' => $restrictedUntil]);

        $this->notifications->send(
            $user,
            'moderation_restriction',
            '⏳ Temporarily restricted',
            "You're restricted from posting, commenting, and reporting prices until {$restrictedUntil->format('M j, Y')}. Reason: {$reason}.",
            ['strike_id' => $strike->id, 'restricted_until' => $restrictedUntil->toIso8601String()],
            '/account-status',
        );

        return $strike;
    }

    public function ban(User $user, string $reason, User $admin, ?ContentReport $contentReport = null, ?ListingReport $listingReport = null): UserStrike
    {
        // Permanent -- expires_at null, never ages off. Reachable regardless
        // of the user's current strike count (the severity-override path).
        $strike = $this->createStrike($user, UserStrike::LEVEL_BAN, $reason, $admin, $contentReport, $listingReport, null);

        $user->update(['banned_at' => now(), 'ban_reason' => $reason]);
        $user->tokens()->delete();

        $this->notifications->send(
            $user,
            'moderation_ban',
            '🚫 Account suspended',
            "Your account has been suspended. Reason: {$reason}.",
            ['strike_id' => $strike->id],
            null,
        );

        return $strike;
    }

    public function unban(User $user): void
    {
        $user->update(['banned_at' => null, 'ban_reason' => null]);
    }

    private function createStrike(
        User $user,
        int $level,
        string $reason,
        User $admin,
        ?ContentReport $contentReport,
        ?ListingReport $listingReport,
        ?\Illuminate\Support\Carbon $expiresAt,
    ): UserStrike {
        return UserStrike::create([
            'user_id' => $user->id,
            'level' => $level,
            'reason' => $reason,
            'content_report_id' => $contentReport?->id,
            'listing_report_id' => $listingReport?->id,
            'issued_by' => $admin->id,
            'expires_at' => $expiresAt,
        ]);
    }

    private function strikeExpiry(): \Illuminate\Support\Carbon
    {
        return now()->addMonths(config('moderation.strike_expiry_months'));
    }
}
