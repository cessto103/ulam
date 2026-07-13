<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\NotificationService;
use App\Services\SellerSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSubscriptionLifecycle extends Command
{
    protected $signature = 'billing:process-lifecycle';
    protected $description = 'Send renewal reminders and move subscriptions through grace, expiry, and suspension';

    public function handle(NotificationService $notifications, SellerSubscriptionService $visibility): int
    {
        Subscription::with('user')->where('status', 'active')
            ->whereBetween('current_period_end', [now()->addDays(2)->startOfDay(), now()->addDays(3)->endOfDay()])
            ->whereDoesntHave('user.notifications', fn ($q) => $q->where('type', 'subscription_expiring')->where('created_at', '>=', now()->subDays(2)))
            ->each(fn ($sub) => $notifications->send($sub->user, 'subscription_expiring', 'Subscription expiring soon',
                "Your seller plan expires on {$sub->current_period_end->format('M j, Y')}.", ['subscription_id' => $sub->id], '/subscription'));

        Subscription::with('user')->where('status', 'active')->where('current_period_end', '<=', now())
            ->chunkById(100, function ($subscriptions) use ($notifications, $visibility) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, $notifications, $visibility) {
                        $subscription->refresh();
                        if ($subscription->status !== 'active') return;
                        if ($subscription->cancel_at_period_end) {
                            $subscription->update(['status' => 'expired']);
                        } else {
                            $subscription->update(['status' => 'grace_period', 'grace_ends_at' => now()->addDays(config('billing.grace_days'))]);
                            $notifications->send($subscription->user, 'subscription_grace', 'Subscription payment needed',
                                'Your plan is in its grace period. Renew to avoid suspension.', ['subscription_id' => $subscription->id], '/subscription');
                        }
                        $visibility->applyEntitlements($subscription->user);
                    });
                }
            });

        Subscription::with('user')->where('status', 'grace_period')->where('grace_ends_at', '<=', now())
            ->each(function ($subscription) use ($notifications, $visibility) {
                $subscription->update(['status' => 'suspended', 'suspended_at' => now()]);
                $visibility->applyEntitlements($subscription->user);
                $notifications->send($subscription->user, 'subscription_suspended', 'Seller plan suspended',
                    'Your grace period ended. Renew to restore your stores and plan features.', ['subscription_id' => $subscription->id], '/subscription');
            });

        return self::SUCCESS;
    }
}
