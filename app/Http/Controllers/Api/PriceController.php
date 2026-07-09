<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPriceReport;
use App\Models\GovernmentPriceReference;
use App\Models\MarketPrice;
use App\Models\Tindahan;
use App\Services\XpService;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    // Official DA Bantay Presyo / DTI SRP references for the user's region (falls back to National)
    private function officialPricesFor(string $itemQuery, $user)
    {
        return GovernmentPriceReference::where('item_name', 'like', "%{$itemQuery}%")
            ->where(function ($q) use ($user) {
                $q->where('region', $user->region)
                  ->orWhere('region', 'National');
            })
            ->orderByDesc('bulletin_date')
            ->limit(10)
            ->get(['id', 'source', 'item_name', 'category', 'price_min', 'price_max', 'unit', 'region', 'bulletin_date', 'source_note']);
    }

    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:50'],
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = $request->radius_km ?? 5;

        // Haversine formula — no Google Maps needed
        $tindahan = Tindahan::selectRaw(
            '*, ( 6371 * acos( cos( radians(?) ) * cos( radians(latitude) ) * cos( radians(longitude) - radians(?) ) + sin( radians(?) ) * sin( radians(latitude) ) ) ) AS distance_km',
            [$lat, $lng, $lat]
        )
            ->having('distance_km', '<=', $radius)
            ->where('is_active', true)
            ->orderBy('distance_km')
            ->with('prices')
            ->limit(20)
            ->get();

        return response()->json(['tindahan' => $tindahan]);
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'reported_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'tindahan_id' => ['nullable', 'integer', 'exists:tindahan,id'],
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $report = CommunityPriceReport::create([
            ...$validated,
            'user_id'      => $user->id,
            'barangay'     => $validated['barangay'] ?? $user->barangay,
            'municipality' => $validated['municipality'] ?? $user->municipality,
            'province'     => $user->province,
        ]);

        app(XpService::class)->award($user, 15, 'report_price', $report);

        return response()->json(['report' => $report, 'xp_earned' => 15], 201);
    }

    public function search(Request $request)
    {
        $q    = trim($request->get('q', ''));
        $user = $request->user();

        if (! $q) {
            return response()->json(['item' => null, 'entries' => []]);
        }

        // Combine market prices + community reports into a flat list
        $marketPrices = MarketPrice::where('item_name', 'like', "%{$q}%")
            ->where('is_available', true)
            ->with([
                'tindahan:id,name,type,municipality',
                'market:id,name,type,municipality',
            ])
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'store_name' => $p->tindahan?->name ?? $p->market?->name ?? '—',
                'store_type' => $p->tindahan?->type ?? $p->market?->type ?? 'tindahan',
                'price'      => (float) $p->price_per_unit,
                'unit'       => $p->unit,
                'updated_at' => $p->updated_at,
            ]);

        $communityReports = CommunityPriceReport::where('item_name', 'like', "%{$q}%")
            ->where('municipality', $user->municipality ?? '')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('upvotes')
            ->with(['tindahan:id,name', 'market:id,name'])
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'store_name'  => $r->tindahan?->name ?? $r->market?->name ?? ($r->barangay ?? 'Local'),
                'store_type'  => 'Community',
                'distance_km' => 0.0,
                'price'       => (float) $r->reported_price,
                'unit'        => $r->unit,
                'updated_at'  => $r->created_at,
            ]);

        // Both are Eloquent collections mapped down to plain arrays — merge() would call
        // ->getKey() on each item expecting Models, so concat() (no key dictionary) instead.
        $entries = $marketPrices->concat($communityReports)->sortBy('price')->values();

        return response()->json([
            'item'     => ucfirst($q),
            'entries'  => $entries,
            'official' => $this->officialPricesFor($q, $user),
        ]);
    }

    public function item(Request $request, string $name)
    {
        $user = $request->user();

        $reports = CommunityPriceReport::where('item_name', 'like', "%{$name}%")
            ->where(function ($q) use ($user) {
                $q->where('municipality', $user->municipality)
                    ->orWhere('province', $user->province);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('upvotes')
            ->limit(10)
            ->get();

        $marketPrices = MarketPrice::where('item_name', 'like', "%{$name}%")
            ->where('is_available', true)
            ->with('tindahan:id,name,municipality')
            ->limit(10)
            ->get();

        return response()->json([
            'community_reports' => $reports,
            'market_prices' => $marketPrices,
            'official_prices' => $this->officialPricesFor($name, $user),
        ]);
    }

    public function vote(Request $request, int $id)
    {
        $request->validate(['vote' => ['required', 'in:up,down']]);

        $report = CommunityPriceReport::findOrFail($id);

        if ($request->vote === 'up') {
            $report->increment('upvotes');
        } else {
            $report->increment('downvotes');
        }

        return response()->json(['report' => $report->fresh()]);
    }

    // GET /prices/history/{item}  — last 30 days of community reports, aggregated by day
    public function history(Request $request, string $item): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $days = (int) ($request->get('days', 30));
        $days = min(max($days, 7), 90);

        $rows = CommunityPriceReport::selectRaw(
                'DATE(created_at) as date,
                 ROUND(AVG(reported_price), 2) as avg,
                 MIN(reported_price) as min,
                 MAX(reported_price) as max,
                 COUNT(*) as report_count'
            )
            ->where('item_name', 'like', "%{$item}%")
            ->where(function ($q) use ($user) {
                $q->where('municipality', $user->municipality)
                  ->orWhere('province', $user->province);
            })
            ->where('created_at', '>=', now()->subDays($days))
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // Also include recent individual reports for the list below the chart
        $recent = CommunityPriceReport::where('item_name', 'like', "%{$item}%")
            ->where(function ($q) use ($user) {
                $q->where('municipality', $user->municipality)
                  ->orWhere('province', $user->province);
            })
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'item_name', 'reported_price', 'unit', 'barangay', 'municipality', 'created_at', 'upvotes']);

        return response()->json([
            'item'     => ucfirst($item),
            'days'     => $days,
            'chart'    => $rows,
            'recent'   => $recent,
            'official' => $this->officialPricesFor($item, $user),
        ]);
    }
}
