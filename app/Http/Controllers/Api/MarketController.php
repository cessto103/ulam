<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Services\MarketDiscoveryService;
use App\Services\PriceIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketController extends Controller
{
    private function haversineNearby(float $lat, float $lng, float $radiusKm = 15)
    {
        return Market::selectRaw(
            '*, ( 6371 * acos( cos( radians(?) ) * cos( radians(latitude) )
                   * cos( radians(longitude) - radians(?) )
                   + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance_km',
            [$lat, $lng, $lat]
        )
            ->where('is_active', true)
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->with(['tindahan' => fn($q) => $q->publiclyVisible()])
            ->get();
    }

    // User-submitted stores that aren't attached to any market (e.g. a home-based
    // tindahan) don't show up on any market page, so they need their own nearby lookup
    // to appear in "Near Me" search results.
    private function haversineNearbyStandaloneTindahan(float $lat, float $lng, float $radiusKm = 15)
    {
        return Tindahan::selectRaw(
            '*, ( 6371 * acos( cos( radians(?) ) * cos( radians(latitude) )
                   * cos( radians(longitude) - radians(?) )
                   + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance_km',
            [$lat, $lng, $lat]
        )
            ->whereNull('market_id')
            ->publiclyVisible()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km')
            ->with(['prices' => fn($q) => $q->where('is_available', true)])
            ->get();
    }

    // If no markets exist near this GPS point yet, look them up via free OpenStreetMap
    // data (Overpass + Nominatim — no AI/paid API) and save them, so the area "fills
    // itself in" the first time anyone searches there. Throttled per ~11km grid cell
    // so repeat searches in the same area don't re-query OSM every time (once tried,
    // skip for a week even if nothing was found — mainly courtesy to the free public
    // OSM endpoints, which have fair-use rate limits).
    private function discoverNearbyMarkets(float $lat, float $lng, MarketDiscoveryService $service)
    {
        $gridKey = 'market_discovery:' . round($lat, 1) . ',' . round($lng, 1);

        if (Cache::has($gridKey)) {
            return collect();
        }
        Cache::put($gridKey, true, now()->addDays(7));

        try {
            $service->discoverMarkets($lat, $lng);
        } catch (\Throwable $e) {
            Log::error('MarketController: auto-discovery failed', ['lat' => $lat, 'lng' => $lng, 'error' => $e->getMessage()]);
            return collect();
        }

        return $this->haversineNearby($lat, $lng);
    }

    public function index(Request $request, MarketDiscoveryService $discovery)
    {
        $user = $request->user();
        $lat  = $request->query('lat');
        $lng  = $request->query('lng');

        if ($lat !== null && $lng !== null) {
            $lat = (float) $lat;
            $lng = (float) $lng;
            $radiusKm = min(30, max(1, (float) $request->query('radius_km', 15)));

            $markets = $this->haversineNearby($lat, $lng, $radiusKm);
            $standaloneTindahan = $this->haversineNearbyStandaloneTindahan($lat, $lng, $radiusKm);

            if ($markets->isEmpty() && $standaloneTindahan->isEmpty()) {
                $markets = $this->discoverNearbyMarkets($lat, $lng, $discovery);
            }
        } else {
            // ── Municipality-based fallback ────────────────────────────────────
            $municipalityMatch = function ($q) use ($user) {
                // loose match: "Antipolo City" or "Antipolo"
                $base = preg_replace('/\s+city$/i', '', $user->municipality);
                $q->where('municipality', $user->municipality)
                  ->orWhere('municipality', $base)
                  ->orWhere('municipality', $base . ' City');
            };

            $markets = Market::where('is_active', true)
                ->where($municipalityMatch)
                ->with(['tindahan' => fn($q) => $q->publiclyVisible()])
                ->get();

            $standaloneTindahan = Tindahan::publiclyVisible()
                ->whereNull('market_id')
                ->where($municipalityMatch)
                ->with(['prices' => fn($q) => $q->where('is_available', true)])
                ->get();
        }

        $marketRows = $markets->map(function ($market) {
            $tindahanIds = $market->tindahan->pluck('id');

            // Prices via tindahan
            $fromStalls = MarketPrice::whereIn('tindahan_id', $tindahanIds)
                ->where('is_available', true);

            // Prices attached directly to market (supermarkets, no tindahan)
            $direct = MarketPrice::where('market_id', $market->id)
                ->whereNull('tindahan_id')
                ->where('is_available', true);

            $priceCount  = $fromStalls->count() + $direct->count();
            $latestStall = $fromStalls->max('updated_at');
            $latestDir   = $direct->max('updated_at');
            $latestUpdate = max($latestStall, $latestDir) ?: null;

            $row = [
                'id'           => $market->id,
                'kind'         => 'market',
                'name'         => $market->name,
                'type'         => $market->type,
                'barangay'     => $market->barangay,
                'municipality' => $market->municipality,
                'latitude'     => $market->latitude,
                'longitude'    => $market->longitude,
                'stall_count'  => $market->tindahan->count(),
                'item_count'   => $priceCount,
                'last_updated' => $latestUpdate,
                'source'       => $market->source ?? 'ulam',
            ];

            if (isset($market->distance_km)) {
                $row['distance_km'] = round((float) $market->distance_km, 2);
            }

            return $row;
        });

        $tindahanRows = $standaloneTindahan->map(function ($tindahan) {
            $row = [
                'id'           => $tindahan->id,
                'kind'         => 'tindahan',
                'name'         => $tindahan->name,
                'type'         => $tindahan->type,
                'barangay'     => $tindahan->barangay,
                'municipality' => $tindahan->municipality,
                'latitude'     => $tindahan->latitude,
                'longitude'    => $tindahan->longitude,
                'stall_count'  => null,
                'item_count'   => $tindahan->prices->count(),
                'last_updated' => $tindahan->prices->max('updated_at'),
                'source'       => 'ulam',
                'is_verified'  => (bool) $tindahan->is_verified,
            ];

            if (isset($tindahan->distance_km)) {
                $row['distance_km'] = round((float) $tindahan->distance_km, 2);
            }

            return $row;
        });

        $result = $marketRows->concat($tindahanRows);

        if (isset($lat)) {
            $result = $result->sortBy('distance_km')->values();
        }

        return response()->json(['markets' => $result]);
    }

    // A user submitting a market/palengke/mall that isn't in the app yet
    public function store(Request $request, MarketDiscoveryService $discovery)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:wet_market,palengke,supermarket,grocery,tindahan'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $locality = $discovery->reverseGeocode($validated['latitude'], $validated['longitude']);

        $market = Market::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'barangay' => $locality['barangay'],
            'municipality' => $locality['municipality'],
            'province' => $locality['province'],
            'region' => $locality['region'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_active' => true,
        ]);

        return response()->json(['market' => $market], 201);
    }

    public function show(int $id)
    {
        $market = Market::where('is_active', true)->findOrFail($id);

        // ── Prices via tindahan ────────────────────────────────────────────────
        $tindahan = $market->tindahan()
            ->where('is_active', true)
            ->with(['prices' => fn($q) => $q->where('is_available', true)->orderBy('item_name')])
            ->get();

        // ── Prices attached directly to market (e.g. supermarkets) ────────────
        $directPrices = MarketPrice::where('market_id', $market->id)
            ->whereNull('tindahan_id')
            ->where('is_available', true)
            ->orderBy('item_name')
            ->get();

        $byCategory = [];

        // Stall prices
        foreach ($tindahan as $stall) {
            foreach ($stall->prices as $price) {
                $cat = $price->category ?: 'iba pa';
                $byCategory[$cat][] = [
                    'id'         => $price->id,
                    'item_name'  => $price->item_name,
                    'price'      => (float) $price->price_per_unit,
                    'unit'       => $price->unit,
                    'stall_name' => $stall->name,
                    'stall_type' => $stall->type,
                    'updated_at' => $price->updated_at,
                ];
            }
        }

        // Direct market prices (shown as the market name as the "stall")
        foreach ($directPrices as $price) {
            $cat = $price->category ?: 'iba pa';
            $byCategory[$cat][] = [
                'id'         => $price->id,
                'item_name'  => $price->item_name,
                'price'      => (float) $price->price_per_unit,
                'unit'       => $price->unit,
                'stall_name' => $market->name,
                'stall_type' => $market->type,
                'updated_at' => $price->updated_at,
            ];
        }

        ksort($byCategory);
        foreach ($byCategory as &$items) {
            usort($items, fn($a, $b) => $a['price'] <=> $b['price']);
        }

        return response()->json([
            'market'      => [
                'id'          => $market->id,
                'name'        => $market->name,
                'type'        => $market->type,
                'barangay'    => $market->barangay,
                'municipality'=> $market->municipality,
                'latitude'    => $market->latitude,
                'longitude'   => $market->longitude,
                'source'      => $market->source ?? 'ulam',
            ],
            'stalls'      => $tindahan->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'type' => $s->type]),
            'by_category' => $byCategory,
        ]);
    }

    public function refreshPrices(int $id, PriceIntelligenceService $service)
    {
        $market = Market::where('is_active', true)->findOrFail($id);

        try {
            $count = $service->refreshMarket($market);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Price refresh failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => "Refreshed {$count} prices for {$market->name}",
            'count'   => $count,
        ]);
    }
}
