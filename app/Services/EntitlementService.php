<?php

namespace App\Services;

use App\Models\SellerPlan;
use App\Models\Subscription;
use App\Models\User;

class EntitlementService
{
    public function subscriptionFor(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)->entitled()->latest('current_period_end')->first();
    }

    public function planFor(User $user): SellerPlan
    {
        return $this->subscriptionFor($user)?->plan ?? SellerPlan::free();
    }

    public function allFor(User $user): array
    {
        $subscription = $this->subscriptionFor($user);
        $plan = $subscription?->plan ?? SellerPlan::free();
        $custom = $plan->features()->get()->mapWithKeys(function ($feature) {
            $raw = $feature->pivot->value;
            $value = match ($feature->value_type) {
                'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $raw,
                'decimal' => (float) $raw,
                default => $raw,
            };
            return [$feature->key => $value];
        })->all();

        return array_merge([
            'stores.max' => $plan->max_stores,
            'store_items.max_per_store' => $plan->max_items_per_store,
        ], $custom);
    }

    public function snapshot(User $user): array
    {
        $subscription = $this->subscriptionFor($user);
        $plan = $subscription?->plan ?? SellerPlan::free();

        return [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'plan_slug' => $plan->slug,
                'plan_name' => $plan->name,
                'current_period_end' => $subscription->current_period_end,
                'grace_ends_at' => $subscription->grace_ends_at,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
            ] : [
                'id' => null, 'status' => 'free', 'plan_slug' => $plan->slug,
                'plan_name' => $plan->name, 'current_period_end' => null,
                'grace_ends_at' => null, 'cancel_at_period_end' => false,
            ],
            'entitlements' => $this->allFor($user),
        ];
    }
}
