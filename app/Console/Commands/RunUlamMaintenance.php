<?php

namespace App\Console\Commands;

use App\Models\AdBoost;
use App\Models\AdSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SellerSubscriptionService;
use Illuminate\Console\Command;

class RunUlamMaintenance extends Command
{
    protected $signature = 'ulam:maintenance';

    protected $description = 'Expire ended seller subscriptions/boosts, send renewal reminders, prune stale OTPs';

    public function handle(NotificationService $notifications, SellerSubscriptionService $sellerService): int
    {
        // 1. Renewal reminders — 3 days before a manual-GCash subscription ends.
        $expiring = AdSubscription::where('type', 'tindahan_listing')
            ->where('status', 'active')
            ->where('renewal_notified', false)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(3)])
            ->with('user')
            ->get();

        foreach ($expiring as $sub) {
            if ($sub->user) {
                $notifications->send(
                    $sub->user,
                    'seller_subscription',
                    'Subscription ending soon ⏰',
                    "Your {$sub->plan} subscription ends on {$sub->expires_at->timezone('Asia/Manila')->format('M j')}. Renew to keep your store running!",
                    ['ad_subscription_id' => $sub->id],
                    '/subscription',
                );
            }
            $sub->update(['renewal_notified' => true]);
        }
        $this->info("Renewal reminders sent: {$expiring->count()}");

        // 2. Flip ended subscriptions to expired + re-sync store visibility.
        $endedSubs = AdSubscription::where('type', 'tindahan_listing')
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('user')
            ->get();

        foreach ($endedSubs as $sub) {
            $sub->update(['status' => 'expired']);
            if ($sub->user) {
                $sellerService->applyEntitlements($sub->user);
                $notifications->send(
                    $sub->user,
                    'seller_subscription',
                    'Subscription ended',
                    'Your seller subscription has ended and your account is back on the Free plan. Renew anytime!',
                    ['ad_subscription_id' => $sub->id],
                    '/subscription',
                );
            }
        }
        $this->info("Subscriptions expired: {$endedSubs->count()}");

        // 3. Flip ended boosts to expired (queries already exclude them; this keeps admin lists honest).
        $endedBoosts = AdBoost::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
        $this->info("Boosts expired: {$endedBoosts}");

        // 4. Prune OTPs that expired more than a day ago.
        $pruned = User::whereNotNull('secondary_email_otp')
            ->where('secondary_email_otp_expires_at', '<', now()->subDay())
            ->update(['secondary_email_otp' => null, 'secondary_email_otp_expires_at' => null]);
        $pruned += User::whereNotNull('password_reset_otp')
            ->where('password_reset_otp_expires_at', '<', now()->subDay())
            ->update(['password_reset_otp' => null, 'password_reset_otp_expires_at' => null]);
        $this->info("Stale OTPs pruned: {$pruned}");

        return self::SUCCESS;
    }
}
