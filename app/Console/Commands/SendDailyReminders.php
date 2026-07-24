<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDailyReminders extends Command
{
    protected $signature   = 'ulam:daily-reminders';
    protected $description = 'Send daily spending-log reminder to active users';

    public function handle(NotificationService $notifs): void
    {
        $today = today()->toDateString();

        // Every user who hasn't logged spending today -- a push token is no
        // longer required here. The bell/in-app notification (UserNotification
        // row below) doesn't need one at all; push is just an extra for
        // whoever happens to have a token, sent separately below.
        $users = User::whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('daily_budget_logs')
                    ->whereColumn('daily_budget_logs.user_id', 'users.id')
                    ->where('daily_budget_logs.log_date', $today);
            })
            ->select('id', 'push_token')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users to notify.');
            return;
        }

        // Bulk push — one HTTP call for whichever subset has a token (sendBulk
        // already no-ops safely on an empty array)
        $tokens = $users->pluck('push_token')->filter()->values()->all();
        $notifs->sendBulk(
            $tokens,
            "🍳 Don't forget to log today's spending!",
            'A quick reminder to track your food budget for today.',
            ['action_url' => '/log-spending', 'type' => 'daily_reminder'],
        );

        // Create individual DB notification records in one insert — for
        // every relevant user, token or not
        $now  = now();
        $rows = $users->map(fn ($u) => [
            'user_id'    => $u->id,
            'type'       => 'daily_reminder',
            'title'      => "🍳 Don't forget to log today's spending!",
            'body'       => 'A quick reminder to track your food budget for today.',
            'data'       => json_encode(['action_url' => '/log-spending']),
            'action_url' => '/log-spending',
            'read_at'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            UserNotification::insert($chunk);
        }

        $this->info("Sent reminders to {$users->count()} users (" . count($tokens) . " via push, rest bell-only).");
    }
}
