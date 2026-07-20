<?php

namespace App\Console\Commands;

use App\Models\UserStrike;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * A strike's "active" state is always computed on the fly from expires_at --
 * this command is not load-bearing for that mechanic (a restriction lifts
 * correctly with zero job runs, purely by restricted_until passing). This
 * only sends a courtesy "your record is now clear" notification for strikes
 * that recently aged off, so it's safe to skip/cut without breaking anything
 * else in the moderation system.
 */
class ExpireUserStrikes extends Command
{
    protected $signature = 'ulam:expire-strikes';
    protected $description = 'Notify users whose strikes recently expired that their record is clear';

    public function handle(NotificationService $notifications): int
    {
        UserStrike::with('user')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->subDay(), now()])
            ->whereDoesntHave('user.notifications', fn ($q) => $q->where('type', 'moderation_strike_expired')->where('created_at', '>=', now()->subDay()))
            ->get()
            ->unique('user_id')
            ->each(fn ($strike) => $notifications->send(
                $strike->user,
                'moderation_strike_expired',
                '✅ Your record is clear',
                'Your recent community guidelines strike has expired and no longer counts against your account.',
                ['strike_id' => $strike->id],
                '/account-status',
            ));

        return self::SUCCESS;
    }
}
