<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdSubscription;
use App\Models\AppSetting;
use App\Models\BoostOption;
use App\Models\SellerPlan;
use Illuminate\Http\Request;

class SellerSubscriptionController extends Controller
{
    /**
     * GET /seller/plans — everything the subscription screen needs in one call:
     * the catalog, GCash payment settings, and the caller's current tier + usage.
     */
    public function catalog(Request $request)
    {
        $user = $request->user();

        $plans = SellerPlan::where('is_active', true)
            ->with(['prices' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort')
            ->get()
            ->map(fn (SellerPlan $plan) => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'tagline' => $plan->tagline,
                'max_stores' => $plan->max_stores,
                'max_items_per_store' => $plan->max_items_per_store,
                'prices' => $plan->prices->map(fn ($p) => [
                    'duration' => $p->duration,
                    'days' => $p->days(),
                    'price' => (float) $p->price,
                ])->values(),
            ]);

        $boosts = BoostOption::where('is_active', true)
            ->orderBy('sort')
            ->get(['target', 'duration_days', 'price'])
            ->map(fn ($b) => [
                'target' => $b->target,
                'duration_days' => $b->duration_days,
                'price' => (float) $b->price,
            ]);

        $settings = AppSetting::allCached();

        $active = $user->activeSellerSubscription();
        $pending = $user->sellerSubscriptions()->where('status', 'pending')->latest()->first();
        $plan = $user->sellerPlan();

        $stores = $user->tindahan()
            ->where('is_active', true)
            ->withCount('prices')
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'hidden_by_plan', 'is_active', 'updated_at'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'items_count' => $t->prices_count,
                'hidden_by_plan' => $t->hidden_by_plan,
            ]);

        return response()->json([
            'plans' => $plans,
            'boosts' => $boosts,
            'payment' => [
                'payments_enabled' => ($settings['payments_enabled'] ?? '1') === '1',
                'gcash_number' => $settings['gcash_number'] ?? null,
                'gcash_account_name' => $settings['gcash_account_name'] ?? null,
                'payment_instructions' => $settings['payment_instructions'] ?? null,
                'support_note' => $settings['payment_support_note'] ?? null,
            ],
            'mine' => [
                'plan_slug' => $plan->slug,
                'plan_name' => $plan->name,
                'max_stores' => $plan->max_stores,
                'max_items_per_store' => $plan->max_items_per_store,
                'expires_at' => $active?->expires_at,
                'pending' => $pending ? [
                    'id' => $pending->id,
                    'plan' => $pending->plan,
                    'duration' => $pending->duration,
                    'amount' => (float) $pending->amount_paid,
                    'payment_reference' => $pending->payment_reference,
                    'submitted_at' => $pending->created_at,
                ] : null,
                'stores' => $stores,
            ],
        ]);
    }

    /** GET /seller/subscriptions — the caller's submission history. */
    public function index(Request $request)
    {
        $subs = $request->user()->sellerSubscriptions()
            ->orderByDesc('created_at')
            ->get([
                'id', 'plan', 'duration', 'amount_paid', 'payment_method',
                'payment_reference', 'status', 'rejected_reason',
                'starts_at', 'expires_at', 'created_at',
            ]);

        return response()->json(['subscriptions' => $subs]);
    }

    /** POST /seller/subscriptions — submit a manual GCash payment for review. */
    public function store(Request $request)
    {
        $settings = AppSetting::allCached();
        if (($settings['payments_enabled'] ?? '1') !== '1') {
            return response()->json(['message' => 'Payments are temporarily unavailable. Please try again later.'], 503);
        }

        $validated = $request->validate([
            'plan' => ['required', 'string', 'exists:seller_plans,slug'],
            'duration' => ['required', 'string', 'in:7d,15d,1m,1y'],
            // GCash reference numbers are numeric (typically 13 digits); accept a
            // tolerant range so legitimate refs are never blocked by format.
            'payment_reference' => ['required', 'regex:/^[0-9]{8,20}$/', 'unique:ad_subscriptions,payment_reference'],
        ], [
            'payment_reference.regex' => 'The reference number should be the digits from your GCash receipt.',
            'payment_reference.unique' => 'This reference number has already been used.',
        ]);

        $plan = SellerPlan::where('slug', $validated['plan'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($plan->slug === SellerPlan::FREE_SLUG) {
            return response()->json(['message' => 'The Free plan does not need a payment.'], 422);
        }

        $price = $plan->prices()
            ->where('duration', $validated['duration'])
            ->where('is_active', true)
            ->first();

        if (! $price) {
            return response()->json(['message' => 'That plan/duration combination is not available.'], 422);
        }

        $user = $request->user();

        if ($user->sellerSubscriptions()->where('status', 'pending')->exists()) {
            return response()->json([
                'message' => 'You already have a payment waiting for review. Please wait for it to be checked first.',
            ], 422);
        }

        $submission = AdSubscription::create([
            'user_id' => $user->id,
            'type' => 'tindahan_listing',
            'plan' => $plan->slug,
            'duration' => $validated['duration'],
            'amount_paid' => $price->price,
            'payment_method' => 'gcash_manual',
            'payment_reference' => $validated['payment_reference'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Salamat! Your payment is being verified — usually within 24 hours.',
            'subscription' => $submission,
        ], 201);
    }

    /** DELETE /seller/subscriptions/{id} — withdraw a pending submission (e.g. typo'd reference). */
    public function destroy(Request $request, int $id)
    {
        $submission = $request->user()->sellerSubscriptions()->findOrFail($id);

        if ($submission->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be withdrawn.'], 422);
        }

        $submission->delete();

        return response()->json(['message' => 'Submission withdrawn.']);
    }
}
