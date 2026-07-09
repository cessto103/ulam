<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Services\MarketDiscoveryService;
use Illuminate\Http\Request;

class TindahanController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    // Validation rules for the per-day { closed, open, close } structure — open/close
    // are 24h "H:i" strings (e.g. "08:00", "21:30"); the client computes open/closed
    // status locally against the device clock, so no timezone handling happens here.
    private function storeHoursRules(): array
    {
        $rules = ['store_hours' => ['nullable', 'array']];
        foreach (self::DAYS as $day) {
            $rules["store_hours.{$day}"] = ['nullable', 'array'];
            $rules["store_hours.{$day}.closed"] = ['nullable', 'boolean'];
            $rules["store_hours.{$day}.open"] = ['nullable', 'date_format:H:i'];
            $rules["store_hours.{$day}.close"] = ['nullable', 'date_format:H:i'];
        }
        return $rules;
    }

    // Keep only the known day keys / fields — drop anything unexpected before saving.
    private function sanitizeStoreHours(?array $hours): ?array
    {
        if (!$hours) {
            return null;
        }
        $clean = [];
        foreach (self::DAYS as $day) {
            if (!isset($hours[$day])) continue;
            $clean[$day] = [
                'closed' => (bool) ($hours[$day]['closed'] ?? false),
                'open' => $hours[$day]['open'] ?? null,
                'close' => $hours[$day]['close'] ?? null,
            ];
        }
        return $clean ?: null;
    }

    // A user's own submitted stores/stalls
    public function mine(Request $request)
    {
        $tindahan = Tindahan::where('user_id', $request->user()->id)
            ->with('market:id,name,type')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['tindahan' => $tindahan]);
    }

    public function show(int $id)
    {
        $tindahan = Tindahan::where('is_active', true)
            ->with(['market:id,name,type,municipality', 'user:id,name'])
            ->findOrFail($id);

        $prices = MarketPrice::where('tindahan_id', $tindahan->id)
            ->where('is_available', true)
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'category', 'price_per_unit', 'unit', 'updated_at']);

        return response()->json([
            'tindahan' => $tindahan,
            'prices' => $prices,
        ]);
    }

    public function store(Request $request, MarketDiscoveryService $discovery)
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'contact_number' => ['nullable', 'string', 'max:20'],
        ], $this->storeHoursRules()));

        $locality = $discovery->reverseGeocode($validated['latitude'], $validated['longitude']);

        $tindahan = Tindahan::create([
            'user_id' => $request->user()->id,
            'market_id' => $validated['market_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'barangay' => $locality['barangay'],
            'municipality' => $locality['municipality'],
            'province' => $locality['province'],
            'region' => $locality['region'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'contact_number' => $validated['contact_number'] ?? null,
            'store_hours' => $this->sanitizeStoreHours($validated['store_hours'] ?? null),
            'is_active' => true,
            'is_verified' => false,
        ]);

        return response()->json(['tindahan' => $tindahan->load('market:id,name,type')], 201);
    }

    public function update(Request $request, int $id, MarketDiscoveryService $discovery)
    {
        $tindahan = Tindahan::findOrFail($id);

        if ($tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit your own store.'], 403);
        }

        $validated = $request->validate(array_merge([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ], $this->storeHoursRules()));

        if (array_key_exists('store_hours', $validated)) {
            $validated['store_hours'] = $this->sanitizeStoreHours($validated['store_hours']);
        }

        // Re-pinned location — re-derive the address fields so they stay in sync.
        if (isset($validated['latitude'], $validated['longitude'])) {
            $locality = $discovery->reverseGeocode($validated['latitude'], $validated['longitude']);
            $validated['barangay'] = $locality['barangay'];
            $validated['municipality'] = $locality['municipality'];
            $validated['province'] = $locality['province'];
            $validated['region'] = $locality['region'];
        }

        $tindahan->update($validated);

        return response()->json(['tindahan' => $tindahan->fresh('market:id,name,type')]);
    }

    public function destroy(Request $request, int $id)
    {
        $tindahan = Tindahan::findOrFail($id);

        if ($tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own store.'], 403);
        }

        $tindahan->delete();

        return response()->json(['message' => 'Store deleted.']);
    }

    // Store owner adding an item + price to their own store's price list
    public function addPrice(Request $request, int $id)
    {
        $tindahan = Tindahan::findOrFail($id);

        if ($tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only add items to your own store.'], 403);
        }

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
        ]);

        $price = MarketPrice::create([
            'tindahan_id' => $tindahan->id,
            'market_id' => $tindahan->market_id,
            'item_name' => $validated['item_name'],
            'category' => $validated['category'] ?? null,
            'price_per_unit' => $validated['price_per_unit'],
            'unit' => $validated['unit'],
            'is_available' => true,
            'last_updated_by' => $request->user()->id,
        ]);

        return response()->json(['price' => $price], 201);
    }

    // Store owner editing an existing item + price in their own store's price list
    public function updatePrice(Request $request, int $id, int $priceId)
    {
        $tindahan = Tindahan::findOrFail($id);

        if ($tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit items in your own store.'], 403);
        }

        $price = MarketPrice::where('tindahan_id', $tindahan->id)->findOrFail($priceId);

        $validated = $request->validate([
            'item_name' => ['sometimes', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'price_per_unit' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $price->update([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        return response()->json(['price' => $price->fresh()]);
    }
}
