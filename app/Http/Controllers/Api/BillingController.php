<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\AppSetting;
use App\Models\SellerPlan;
use App\Models\SellerPlanPrice;
use App\Services\BillingService;
use App\Services\EntitlementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing, private EntitlementService $entitlements) {}

    public function catalog(Request $request)
    {
        $plans = SellerPlan::where('is_active', true)->with(['prices' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort')->get()->map(fn ($plan) => [
                'slug' => $plan->slug, 'name' => $plan->name, 'tagline' => $plan->tagline,
                'max_stores' => $plan->max_stores, 'max_items_per_store' => $plan->max_items_per_store,
                'prices' => $plan->prices->map(fn ($price) => [
                    'id' => $price->id, 'duration' => $price->duration,
                    'days' => $price->days(), 'amount' => (int) round(((float) $price->price) * 100),
                    'price' => (float) $price->price, 'currency' => 'PHP',
                ])->values(),
            ]);

        $settings = AppSetting::allCached();
        return response()->json(array_merge([
            'plans' => $plans,
            'provider' => config('billing.provider'),
            'payments_enabled' => ($settings['payments_enabled'] ?? '1') === '1',
        ], $this->entitlements->snapshot($request->user())));
    }

    public function status(Request $request)
    {
        return response()->json($this->entitlements->snapshot($request->user()));
    }

    public function history(Request $request)
    {
        return response()->json([
            'subscriptions' => $request->user()->subscriptions()
                ->with(['plan:id,slug,name', 'price:id,duration,price'])
                ->latest()->get(),
            'payments' => \App\Models\Payment::where('user_id', $request->user()->id)
                ->latest()->get(['id', 'provider', 'provider_payment_id', 'plan_type', 'amount', 'currency', 'status', 'failure_code', 'paid_at', 'refunded_at', 'created_at']),
        ]);
    }

    public function checkout(Request $request)
    {
        abort_if((AppSetting::allCached()['payments_enabled'] ?? '1') !== '1', 503, 'Checkout is temporarily unavailable.');
        $validated = $request->validate(['price_id' => [
            'required', 'integer', Rule::exists('seller_plan_prices', 'id')->where('is_active', true),
        ]]);
        $price = SellerPlanPrice::with('plan')->findOrFail($validated['price_id']);
        abort_if(! $price->plan->is_active || $price->plan->slug === SellerPlan::FREE_SLUG, 422, 'This plan cannot be purchased.');

        $session = $this->billing->checkout($request->user(), $price);
        return response()->json([
            'session_id' => $session->public_id, 'status' => $session->status,
            'checkout_url' => $session->checkout_url, 'expires_at' => $session->expires_at,
            'return_url' => config('billing.mobile_return_url'),
        ], 201);
    }

    public function checkoutStatus(Request $request, string $publicId)
    {
        $session = CheckoutSession::where('user_id', $request->user()->id)->where('public_id', $publicId)->firstOrFail();
        return response()->json(['session_id' => $session->public_id, 'status' => $session->status]);
    }

    public function cancel(Request $request, int $id)
    {
        $this->billing->cancel($request->user(), $id);
        return response()->json(['message' => 'Cancellation scheduled for the end of the billing period.']);
    }
}
