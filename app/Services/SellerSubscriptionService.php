<?php

namespace App\Services;

use App\Models\AdSubscription;
use App\Models\Payment;
use App\Models\SellerPlan;
use App\Models\SellerPlanPrice;
use App\Models\User;

class SellerSubscriptionService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Approve a pending manual-payment submission and start (or extend) the tier.
     *
     * Renewal of the same plan stacks: the new period extends from the current
     * expiry, never from today. Switching plans starts immediately and converts
     * the unused peso value of the old subscription into bonus days on the new
     * one (remaining_days x old_daily_rate / new_daily_rate).
     */
    public function approve(AdSubscription $submission, User $admin): AdSubscription
    {
        $days = SellerPlanPrice::DURATION_DAYS[$submission->duration] ?? 30;
        $current = $submission->user->activeSellerSubscription();

        $startsAt = now();
        $expiresAt = now()->addDays($days);

        if ($current && $current->id !== $submission->id) {
            if ($current->plan === $submission->plan) {
                // Same-plan renewal — stack on top of the running period.
                $expiresAt = $current->expires_at->copy()->addDays($days);
            } else {
                // Plan switch — convert unused value into bonus days.
                $expiresAt = $expiresAt->addDays($this->conversionBonusDays($current, $submission, $days));
            }
            $current->update(['status' => 'expired']);
        }

        $submission->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'reviewed_at' => now(),
            'activated_by' => $admin->id,
        ]);

        // Ledger row — same table the PayMongo webhook writes, amounts in centavos.
        Payment::create([
            'user_id' => $submission->user_id,
            'provider' => $submission->payment_method,
            'provider_payment_id' => $submission->payment_reference,
            'plan_type' => "seller:{$submission->plan}:{$submission->duration}",
            'amount' => (int) round($submission->amount_paid * 100),
            'currency' => 'PHP',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => ['ad_subscription_id' => $submission->id],
        ]);

        $this->applyEntitlements($submission->user);

        $planName = $submission->sellerPlan?->name ?? ucfirst($submission->plan);
        $this->notifications->send(
            $submission->user,
            'seller_subscription',
            'Subscription activated! 🎉',
            "Your {$planName} subscription is now active until {$expiresAt->format('M j, Y')}. Thank you for your support!",
            ['ad_subscription_id' => $submission->id],
            '/subscription',
        );

        return $submission->fresh();
    }

    public function reject(AdSubscription $submission, User $admin, string $reason): AdSubscription
    {
        $submission->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'reviewed_at' => now(),
            'activated_by' => $admin->id,
        ]);

        $this->notifications->send(
            $submission->user,
            'seller_subscription',
            'Payment could not be verified',
            "Your subscription payment was declined: {$reason}. Please double-check the reference number and try again.",
            ['ad_subscription_id' => $submission->id],
            '/subscription',
        );

        return $submission->fresh();
    }

    /**
     * Refund an active subscription: access ends immediately (no grace period)
     * and the account falls back to Free entitlements. The peso refund itself
     * happens outside the app (GCash send-back).
     */
    public function refund(AdSubscription $subscription, User $admin): AdSubscription
    {
        $subscription->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'expires_at' => now(),
            'activated_by' => $admin->id,
        ]);

        // Negative ledger row so revenue sums stay honest.
        Payment::create([
            'user_id' => $subscription->user_id,
            'provider' => $subscription->payment_method,
            'provider_payment_id' => $subscription->payment_reference
                ? "refund:{$subscription->payment_reference}"
                : null,
            'plan_type' => "seller:{$subscription->plan}:{$subscription->duration}",
            'amount' => -(int) round($subscription->amount_paid * 100),
            'currency' => 'PHP',
            'status' => 'refunded',
            'paid_at' => now(),
            'meta' => ['ad_subscription_id' => $subscription->id],
        ]);

        $this->applyEntitlements($subscription->user);

        $this->notifications->send(
            $subscription->user,
            'seller_subscription',
            'Subscription refunded',
            'Your subscription has been refunded and ended. Your account is back on the Free plan.',
            ['ad_subscription_id' => $subscription->id],
            '/subscription',
        );

        return $subscription->fresh();
    }

    /**
     * Sync store visibility with the plan in force: unhide up to the limit,
     * hide the overflow (most recently updated stores keep their spot).
     */
    public function applyEntitlements(User $user): void
    {
        $maxStores = $user->sellerPlan()->max_stores;

        $stores = $user->tindahan()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($stores->values() as $i => $store) {
            $shouldHide = $i >= $maxStores;
            if ($store->hidden_by_plan !== $shouldHide) {
                $store->update(['hidden_by_plan' => $shouldHide]);
            }
        }
    }

    private function conversionBonusDays(AdSubscription $old, AdSubscription $new, int $newDays): int
    {
        $remainingDays = max(0, now()->diffInDays($old->expires_at, false));
        $oldDays = SellerPlanPrice::DURATION_DAYS[$old->duration] ?? 30;

        if ($remainingDays <= 0 || $oldDays <= 0 || $new->amount_paid <= 0) {
            return 0;
        }

        $credit = $remainingDays * ((float) $old->amount_paid / $oldDays);
        $newDailyRate = (float) $new->amount_paid / $newDays;

        return (int) floor($credit / $newDailyRate);
    }
}
