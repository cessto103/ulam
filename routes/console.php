<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily spending-log reminder — runs every morning at 8:00 AM
Schedule::command('ulam:daily-reminders')->dailyAt('08:00');

// Daily weather notification, per user location — runs early so it's
// waiting when people check their phone in the morning.
Schedule::command('ulam:weather-daily')->dailyAt('06:00');

// Nightly AI price refresh — runs at 2:00 AM when traffic is low
Schedule::command('prices:refresh-ai')->dailyAt('02:00');

// Nightly DA Bantay Presyo / DTI SRP reference refresh — runs after the market refresh
Schedule::command('prices:refresh-gov')->dailyAt('03:00');

// Subscription reminders, grace periods, expiry, and suspension.
Schedule::command('billing:process-lifecycle')->hourly()->withoutOverlapping();

// Manual-GCash seller subscriptions + boosts: renewal reminders, expiry
// flips (+ store visibility re-sync), and stale OTP pruning.
Schedule::command('ulam:maintenance')->hourly()->withoutOverlapping();

// Courtesy "your record is clear" notification for recently-expired strikes.
// Not load-bearing -- a restriction lifts on its own via restricted_until
// regardless of whether this ever runs.
Schedule::command('ulam:expire-strikes')->dailyAt('04:00');
