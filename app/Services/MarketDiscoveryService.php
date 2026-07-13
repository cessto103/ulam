<?php

namespace App\Services;

use App\Models\Market;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Discovers real markets/stores near a GPS point using free OpenStreetMap data
 * (Overpass API for points of interest, Nominatim for reverse geocoding).
 * No AI/paid API involved — unlike PriceIntelligenceService, which handles prices.
 */
class MarketDiscoveryService
{
    private const RADIUS_METERS = 8000;
    private const MAX_RESULTS = 10;
    private const USER_AGENT = 'uLam-App/1.0 (Filipino household budgeting app)';

    // OSM's "region" address field gives a readable name — map to the PSA region
    // codes used elsewhere in the app (users.region, government_price_references.region).
    private const REGION_CODES = [
        'national capital region' => 'NCR',
        'metro manila' => 'NCR',
        'cordillera administrative region' => 'CAR',
        'ilocos region' => 'I',
        'cagayan valley' => 'II',
        'central luzon' => 'III',
        'calabarzon' => 'IV-A',
        'mimaropa' => 'IV-B',
        'bicol region' => 'V',
        'western visayas' => 'VI',
        'central visayas' => 'VII',
        'eastern visayas' => 'VIII',
        'zamboanga peninsula' => 'IX',
        'northern mindanao' => 'X',
        'davao region' => 'XI',
        'soccsksargen' => 'XII',
        'caraga' => 'XIII',
        'bangsamoro autonomous region in muslim mindanao' => 'BARMM',
    ];

    public function discoverMarkets(float $lat, float $lng): Collection
    {
        $locality = $this->reverseGeocode($lat, $lng);
        $pois = $this->queryOverpass($lat, $lng);

        if ($pois->isEmpty()) {
            Log::info("MarketDiscoveryService: no OSM markets found near {$lat},{$lng}");
            return collect();
        }

        return $this->persist($pois, $locality);
    }

    /**
     * Resolve barangay/municipality/province/region for a GPS point via free
     * Nominatim reverse geocoding. Public so other controllers (user-submitted
     * stores/markets) can reuse it without duplicating the OSM address-parsing logic.
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $address = [];

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 16,
                    'addressdetails' => 1,
                ]);
            $address = $response->json('address', []) ?? [];
        } catch (\Throwable $e) {
            Log::warning('MarketDiscoveryService: reverse geocode failed', ['error' => $e->getMessage()]);
        }

        $regionName = strtolower(trim($address['region'] ?? ''));

        return [
            'barangay'     => $address['suburb'] ?? $address['quarter'] ?? $address['village'] ?? $address['neighbourhood'] ?? 'Unknown',
            'municipality' => $address['city'] ?? $address['town'] ?? $address['municipality'] ?? 'Unknown',
            'province'     => $address['state'] ?? $address['province'] ?? 'Unknown',
            'region'       => self::REGION_CODES[$regionName] ?? ($address['region'] ?? 'Unknown'),
        ];
    }

    private function queryOverpass(float $lat, float $lng): Collection
    {
        $radius = self::RADIUS_METERS;
        // Large stores are often mapped as building outlines ("ways"), not points —
        // querying only nodes silently misses them. `out center` gives ways a centroid.
        $query = <<<QL
        [out:json][timeout:25];
        (
          node["amenity"="marketplace"](around:{$radius},{$lat},{$lng});
          way["amenity"="marketplace"](around:{$radius},{$lat},{$lng});
          node["shop"="supermarket"](around:{$radius},{$lat},{$lng});
          way["shop"="supermarket"](around:{$radius},{$lat},{$lng});
          node["shop"="grocery"](around:{$radius},{$lat},{$lng});
          way["shop"="grocery"](around:{$radius},{$lat},{$lng});
          node["shop"="greengrocer"](around:{$radius},{$lat},{$lng});
          node["shop"="convenience"](around:{$radius},{$lat},{$lng});
          way["shop"="convenience"](around:{$radius},{$lat},{$lng});
        );
        out center 40;
        QL;

        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(20)
                ->asForm()
                ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);
            $elements = $response->json('elements', []) ?? [];
        } catch (\Throwable $e) {
            Log::warning('MarketDiscoveryService: Overpass query failed', ['error' => $e->getMessage()]);
            return collect();
        }

        return collect($elements)
            ->filter(fn ($el) => !empty($el['tags']['name']))
            ->map(function ($el) {
                $tags = $el['tags'];

                return [
                    'name'          => trim($tags['name']),
                    'type'          => $this->mapType($tags),
                    'lat'           => $el['lat'] ?? ($el['center']['lat'] ?? null),
                    'lon'           => $el['lon'] ?? ($el['center']['lon'] ?? null),
                    'addr_city'     => $tags['addr:city'] ?? null,
                    'addr_province' => $tags['addr:province'] ?? $tags['addr:state'] ?? null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lon'] !== null)
            // Wet markets/palengke are the most relevant for a price-checking app — prioritize them.
            ->sortBy(fn ($item) => $item['type'] === 'palengke' ? 0 : 1)
            ->unique('name')
            ->take(self::MAX_RESULTS)
            ->values();
    }

    private function mapType(array $tags): string
    {
        if (($tags['amenity'] ?? null) === 'marketplace') return 'palengke';
        if (($tags['shop'] ?? null) === 'supermarket') return 'supermarket';
        if (($tags['shop'] ?? null) === 'convenience') return 'convenience';

        return 'grocery';
    }

    private function persist(Collection $pois, array $locality): Collection
    {
        $markets = collect();
        foreach ($pois as $poi) {
            $market = Market::updateOrCreate(
                [
                    'name'         => $poi['name'],
                    'municipality' => $poi['addr_city'] ?? $locality['municipality'],
                ],
                [
                    'type'        => $poi['type'],
                    'barangay'    => $locality['barangay'],
                    'province'    => $poi['addr_province'] ?? $locality['province'],
                    'region'      => $locality['region'],
                    'latitude'    => $poi['lat'],
                    'longitude'   => $poi['lon'],
                    'is_active'   => true,
                ]
            );
            // Tag origin only on creation — re-discovery must not relabel a market
            // that a user/admin created and happens to share the same name.
            if ($market->wasRecentlyCreated) {
                $market->update(['source' => 'osm']);
            }
            $markets->push($market);
        }
        Log::info('MarketDiscoveryService: discovered ' . $markets->count() . ' markets via OSM');

        return $markets;
    }
}
