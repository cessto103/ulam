<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Services\WeatherPhraseSelector;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class SendWeatherNotifications extends Command
{
    protected $signature = 'ulam:weather-daily {--dry-run : Log what would be sent without writing or pushing anything}';
    protected $description = 'Send each user a daily weather notification for their own saved location';

    public function handle(WeatherService $weather, WeatherPhraseSelector $selector, NotificationService $notifs): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $users = User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('push_token')
            ->select('id', 'latitude', 'longitude', 'plan', 'premium_expires_at', 'push_token')
            ->get()
            ->groupBy(fn (User $u) => $weather->bucketKey($u->latitude, $u->longitude));

        $totalSent = 0;

        foreach ($users as $bucketKey => $bucketUsers) {
            $first = $bucketUsers->first();
            $forecast = $weather->getForBucket($bucketKey, $first->latitude, $first->longitude);

            if (! $forecast) {
                $this->warn("Skipping bucket {$bucketKey}: forecast unavailable.");
                continue;
            }

            $standard = $selector->selectStandard($forecast);
            $premiumPromo = $forecast->weather_category === 'extended_rain'
                ? $selector->selectPremiumPromo($forecast)
                : null;

            if (! $standard && ! $premiumPromo) {
                $this->warn("Skipping bucket {$bucketKey}: no active weather phrases configured for '{$forecast->weather_category}'.");
                continue;
            }

            $premiumEligible = collect();
            $standardGroup = collect();

            foreach ($bucketUsers as $user) {
                if ($premiumPromo && ! $user->isPremium()) {
                    $premiumEligible->push($user);
                } else {
                    $standardGroup->push($user);
                }
            }

            $totalSent += $this->dispatch($premiumEligible, $premiumPromo, 'weather_premium_promo', $notifs, $dryRun);
            $totalSent += $this->dispatch($standardGroup, $standard, 'weather_update', $notifs, $dryRun);
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Weather notifications sent to {$totalSent} users across {$users->count()} location buckets.");
    }

    private function dispatch($users, ?array $message, string $type, NotificationService $notifs, bool $dryRun): int
    {
        if ($users->isEmpty() || ! $message) {
            return 0;
        }

        if ($dryRun) {
            $this->line("  [{$type}] {$users->count()} users -> \"{$message['title']}\": {$message['body']}");
            return $users->count();
        }

        $now = now();
        $rows = $users->map(fn (User $u) => [
            'user_id' => $u->id,
            'type' => $type,
            'title' => $message['title'],
            'body' => $message['body'],
            'data' => json_encode($message['data']),
            'action_url' => '/weather-detail',
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            UserNotification::insert($chunk);
        }

        $tokens = $users->pluck('push_token')->filter()->values()->all();
        $notifs->sendBulk($tokens, $message['title'], $message['body'], $message['data'] + ['action_url' => '/weather-detail']);

        return $users->count();
    }
}
