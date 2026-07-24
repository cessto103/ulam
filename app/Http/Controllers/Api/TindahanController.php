<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\CommunityPriceReport;
use App\Models\ContentView;
use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Models\TindahanRating;
use App\Services\XpService;
use App\Services\MarketDiscoveryService;
use Illuminate\Http\Request;

class TindahanController extends Controller
{
    /**
     * Seller-tier limit response. The app keys on `upgrade_required` to show
     * the subscription upsell instead of a generic error.
     */
    private function upgradeRequired(string $message, int $limit)
    {
        return response()->json([
            'message' => $message,
            'upgrade_required' => true,
            'limit' => $limit,
        ], 403);
    }

    /** Would adding one more item to this store bust the owner's per-store cap? */
    private function itemLimitReached(Request $request, Tindahan $tindahan): ?int
    {
        $maxItems = $request->user()->sellerPlan()->max_items_per_store;
        $count = MarketPrice::where('tindahan_id', $tindahan->id)->count();

        return $count >= $maxItems ? $maxItems : null;
    }
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

    /**
     * Boosted stores only -- same dedicated placement concept as
     * RecipeController::recommended. Optional lat/lng (+radius_km, default 5)
     * narrows to nearby boosted stores for the dashboard's "Recommended
     * Stores Near You"; without them, returns boosted stores nationwide,
     * most-recently-boosted first, for the Prices page's plain "Recommended
     * Stores" strip. Paginated small (default 4 = a 2x2 grid) to match the
     * mobile "Load More" pattern.
     */
    public function recommended(Request $request)
    {
        $lat     = $request->query('lat');
        $lng     = $request->query('lng');
        $perPage = min(20, max(1, (int) $request->query('per_page', 4)));

        $boosted = function ($q) {
            $q->selectRaw('1')
                ->from('ad_boosts')
                ->whereColumn('ad_boosts.boostable_id', 'tindahan.id')
                ->where('ad_boosts.boostable_type', Tindahan::class)
                ->where('ad_boosts.status', 'active')
                ->where('ad_boosts.expires_at', '>', now());
        };

        if ($lat !== null && $lng !== null) {
            $lat      = (float) $lat;
            $lng      = (float) $lng;
            $radiusKm = min(30, max(1, (float) $request->query('radius_km', 5)));

            $stores = Tindahan::publiclyVisible()
                ->whereNotNull('latitude')->whereNotNull('longitude')
                ->whereExists($boosted)
                ->selectRaw(
                    'tindahan.*, ( 6371 * acos( cos( radians(?) ) * cos( radians(latitude) )
                           * cos( radians(longitude) - radians(?) )
                           + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance_km',
                    [$lat, $lng, $lat]
                )
                ->having('distance_km', '<=', $radiusKm)
                ->orderBy('distance_km')
                ->paginate($perPage);
        } else {
            $stores = Tindahan::publiclyVisible()
                ->whereExists($boosted)
                ->selectRaw(
                    'tindahan.*,
                     (SELECT ad_boosts.created_at FROM ad_boosts
                        WHERE ad_boosts.boostable_type = ? AND ad_boosts.boostable_id = tindahan.id
                          AND ad_boosts.status = ? AND ad_boosts.expires_at > ?
                        ORDER BY ad_boosts.created_at DESC LIMIT 1) as boosted_at',
                    [Tindahan::class, 'active', now()]
                )
                ->orderByDesc('boosted_at')
                ->paginate($perPage);
        }

        $stores->getCollection()->transform(function ($t) {
            $priceQ = MarketPrice::where('tindahan_id', $t->id)->where('is_available', true);
            $t->item_count   = (clone $priceQ)->count();
            $t->last_updated = (clone $priceQ)->max('updated_at');
            return $t;
        });

        return response()->json($stores);
    }

    // A user's own submitted stores/stalls
    /** Pending community price reports for all stores owned by the auth user. */
    public function pendingReports(Request $request)
    {
        $storeIds = Tindahan::where('user_id', $request->user()->id)->pluck('id');

        $reports = CommunityPriceReport::whereIn('tindahan_id', $storeIds)
            ->where('status', 'pending')
            ->with(['user:id,name,username,avatar', 'tindahan:id,name'])
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'tindahan_id', 'item_name', 'category',
                   'reported_price', 'unit', 'photo', 'created_at']);

        return response()->json(['reports' => $reports]);
    }

    /** Accept a report: publish it onto the store's price list. */
    public function acceptReport(Request $request, int $id)
    {
        $report = CommunityPriceReport::with('tindahan')->findOrFail($id);

        if (! $report->tindahan || $report->tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only review reports for your own store.'], 403);
        }
        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been reviewed.'], 422);
        }

        // Accepting a report that introduces a NEW item counts against the
        // per-store item cap — otherwise reports would be a tier loophole.
        $isNewItem = ! MarketPrice::where('tindahan_id', $report->tindahan_id)
            ->where('item_name', $report->item_name)
            ->where('unit', $report->unit)
            ->exists();

        if ($isNewItem && ($limit = $this->itemLimitReached($request, $report->tindahan)) !== null) {
            return $this->upgradeRequired(
                "Your plan allows up to {$limit} items per store. Upgrade to accept more item suggestions.",
                $limit,
            );
        }

        MarketPrice::updateOrCreate(
            [
                'tindahan_id' => $report->tindahan_id,
                'item_name'   => $report->item_name,
                'unit'        => $report->unit,
            ],
            [
                'market_id'       => $report->tindahan->market_id,
                'category'        => $report->category,
                'photo'           => $report->photo,
                'price_per_unit'  => $report->reported_price,
                'is_available'    => true,
                'last_updated_by' => $report->user_id,
            ]
        );

        $report->update(['status' => 'accepted', 'reviewed_at' => now()]);

        // Small thank-you to the reporter for a confirmed price.
        if ($report->user) {
            app(XpService::class)->award($report->user, 5, 'report_accepted', $report);
        }

        return response()->json(['message' => 'Report accepted.', 'report' => $report->fresh()]);
    }

    /** Decline a report with a reason. */
    public function declineReport(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
        ]);

        $report = CommunityPriceReport::with('tindahan')->findOrFail($id);

        if (! $report->tindahan || $report->tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only review reports for your own store.'], 403);
        }
        if ($report->status !== 'pending') {
            return response()->json(['message' => 'This report has already been reviewed.'], 422);
        }

        $report->update([
            'status'          => 'declined',
            'declined_reason' => $validated['reason'],
            'reviewed_at'     => now(),
        ]);

        return response()->json(['message' => 'Report declined.', 'report' => $report->fresh()]);
    }

    /** Upload / replace the store profile photo and header (cover) photo. */
    public function uploadPhotos(Request $request, int $id)
    {
        $tindahan = Tindahan::findOrFail($id);

        if ($tindahan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only update your own store.'], 403);
        }

        $request->validate([
            'photo' => ['nullable', 'image', 'max:4096'],
            'cover' => ['nullable', 'image', 'max:6144'],
        ]);

        if ($request->hasFile('photo')) {
            if ($tindahan->photo && str_starts_with($tindahan->photo, '/storage/')) {
                @unlink(public_path($tindahan->photo));
            }
            $path = $request->file('photo')->store('stores', 'public');
            $tindahan->photo = '/storage/' . $path;
        }

        if ($request->hasFile('cover')) {
            if ($tindahan->cover_photo && str_starts_with($tindahan->cover_photo, '/storage/')) {
                @unlink(public_path($tindahan->cover_photo));
            }
            $path = $request->file('cover')->store('stores', 'public');
            $tindahan->cover_photo = '/storage/' . $path;
        }

        $tindahan->save();

        if ($request->hasFile('photo')) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($tindahan->photo, 'tindahan.photo', $tindahan->id);
        }
        if ($request->hasFile('cover')) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($tindahan->cover_photo, 'tindahan.cover_photo', $tindahan->id);
        }

        return response()->json(['tindahan' => $tindahan->fresh()]);
    }

    public function mine(Request $request)
    {
        $tindahan = Tindahan::where('user_id', $request->user()->id)
            ->with('market:id,name,type')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['tindahan' => $tindahan]);
    }

    public function show(Request $request, int $id)
    {
        // Owners can still open their own plan-hidden stores (to manage them);
        // everyone else only sees publicly visible ones.
        $tindahan = Tindahan::where(function ($q) use ($request) {
            $q->publiclyVisible()->orWhere('user_id', $request->user()?->id ?? 0);
        })
            ->with(['market:id,name,type,municipality', 'user:id,name'])
            ->findOrFail($id);

        if ($request->user()) {
            ContentView::log($tindahan, $request->user(), $tindahan->user_id);
        }

        $prices = MarketPrice::where('tindahan_id', $tindahan->id)
            ->where('is_available', true)
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'category', 'price_per_unit', 'unit', 'photo', 'updated_at']);

        $isBoosted = AdBoost::where('boostable_type', Tindahan::class)
            ->where('boostable_id', $tindahan->id)
            ->active()
            ->exists();

        $myRating = $request->user()
            ? TindahanRating::where('user_id', $request->user()->id)->where('tindahan_id', $id)->value('rating')
            : null;

        return response()->json([
            'tindahan' => $tindahan,
            'prices' => $prices,
            'is_boosted' => $isBoosted,
            'my_rating' => $myRating,
        ]);
    }

    /** POST /tindahan/{id}/rate */
    public function rate(Request $request, int $id)
    {
        $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        $user = $request->user();
        $tindahan = Tindahan::findOrFail($id);

        TindahanRating::updateOrCreate(
            ['user_id' => $user->id, 'tindahan_id' => $id],
            ['rating' => $request->rating]
        );

        $agg = TindahanRating::where('tindahan_id', $id)->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();
        $tindahan->update([
            'average_rating' => round($agg->avg_r, 2),
            'ratings_count' => $agg->cnt,
        ]);

        return response()->json([
            'average_rating' => $tindahan->fresh()->average_rating,
            'ratings_count' => $tindahan->fresh()->ratings_count,
            'my_rating' => $request->rating,
        ]);
    }

    public function store(Request $request, MarketDiscoveryService $discovery)
    {
        $plan = $request->user()->sellerPlan();
        $publicStores = $request->user()->tindahan()->publiclyVisible()->count();

        if ($publicStores >= $plan->max_stores) {
            return $this->upgradeRequired(
                "Your {$plan->name} plan allows {$plan->max_stores} " . ($plan->max_stores === 1 ? 'store' : 'stores') . '. Upgrade to open more.',
                $plan->max_stores,
            );
        }

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

        if (($limit = $this->itemLimitReached($request, $tindahan)) !== null) {
            return $this->upgradeRequired(
                "Your plan allows up to {$limit} items per store. Upgrade to add more.",
                $limit,
            );
        }

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = '/storage/' . $request->file('photo')->store('items', 'public');
        }

        $price = MarketPrice::create([
            'photo' => $photoPath,
            'tindahan_id' => $tindahan->id,
            'market_id' => $tindahan->market_id,
            'item_name' => $validated['item_name'],
            'category' => $validated['category'] ?? null,
            'price_per_unit' => $validated['price_per_unit'],
            'unit' => $validated['unit'],
            'is_available' => true,
            'last_updated_by' => $request->user()->id,
        ]);

        if ($photoPath) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($photoPath, 'market_price.photo', $price->id);
        }

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
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($validated['photo']);
        if ($request->hasFile('photo')) {
            if ($price->photo && str_starts_with($price->photo, '/storage/')) {
                @unlink(public_path($price->photo));
            }
            $validated['photo'] = '/storage/' . $request->file('photo')->store('items', 'public');
        }

        $price->update([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            \App\Jobs\ModerateImageJob::dispatchAfterResponse($price->photo, 'market_price.photo', $price->id);
        }

        return response()->json(['price' => $price->fresh()]);
    }
}
