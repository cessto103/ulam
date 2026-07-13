<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoostOption;
use App\Models\SellerPlan;
use App\Models\Feature;
use Illuminate\Http\Request;

// Plans and boost options are a fixed, seeded catalog — limits and prices are
// editable, rows are not created/deleted from the admin.
class SellerPlanController extends Controller
{
    public function index()
    {
        return response()->json([
            'plans' => SellerPlan::with(['prices', 'features'])->orderBy('sort')->get(),
            'features' => Feature::orderBy('key')->get(),
            'boost_options' => BoostOption::orderBy('sort')->get(),
        ]);
    }

    /** PATCH /admin/seller-plans/{id} — edit tier metadata + limits. */
    public function update(Request $request, int $id)
    {
        $plan = SellerPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'max_stores' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'max_items_per_store' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // The free tier is the fallback for every account — it can't be disabled.
        if ($plan->slug === SellerPlan::FREE_SLUG) {
            unset($validated['is_active']);
        }

        $plan->update($validated);

        return response()->json(['plan' => $plan->fresh('prices')]);
    }

    /** PUT /admin/seller-plans/{id}/prices — bulk upsert the four duration prices. */
    public function updatePrices(Request $request, int $id)
    {
        $plan = SellerPlan::findOrFail($id);

        if ($plan->slug === SellerPlan::FREE_SLUG) {
            return response()->json(['message' => 'The Free plan has no prices.'], 422);
        }

        $validated = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.duration' => ['required', 'in:7d,15d,1m,1y'],
            'prices.*.price' => ['required', 'numeric', 'min:1', 'max:100000'],
            'prices.*.is_active' => ['sometimes', 'boolean'],
        ]);

        foreach ($validated['prices'] as $row) {
            $plan->prices()->updateOrCreate(
                ['duration' => $row['duration']],
                ['price' => $row['price'], 'is_active' => $row['is_active'] ?? true],
            );
        }

        return response()->json(['plan' => $plan->fresh('prices')]);
    }

    public function updateFeatures(Request $request, int $id)
    {
        $plan = SellerPlan::findOrFail($id);
        $validated = $request->validate([
            'features' => ['required', 'array'],
            'features.*.key' => ['required', 'string', 'exists:features,key'],
            'features.*.value' => ['required'],
        ]);

        $ids = Feature::whereIn('key', collect($validated['features'])->pluck('key'))->pluck('id', 'key');
        $sync = [];
        foreach ($validated['features'] as $feature) {
            $value = is_bool($feature['value']) ? ($feature['value'] ? 'true' : 'false') : (string) $feature['value'];
            $sync[$ids[$feature['key']]] = ['value' => $value];
        }
        $plan->features()->sync($sync);

        return response()->json(['plan' => $plan->fresh(['prices', 'features'])]);
    }

    /** PATCH /admin/boost-options/{id} — edit a boost price. */
    public function updateBoostOption(Request $request, int $id)
    {
        $option = BoostOption::findOrFail($id);

        $validated = $request->validate([
            'price' => ['sometimes', 'numeric', 'min:1', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $option->update($validated);

        return response()->json(['boost_option' => $option->fresh()]);
    }
}
