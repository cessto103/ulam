<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Services\PriceIntelligenceService;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        $query = Market::withCount(['tindahan', 'prices']);

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('municipality', 'like', "%{$q}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', $request->string('municipality'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $market = Market::with([
                'user:id,name,email',
                'tindahan' => fn ($q) => $q->withCount('prices')->orderBy('name'),
                'prices' => fn ($q) => $q->with('tindahan:id,name')->orderByDesc('updated_at'),
            ])
            ->withCount(['tindahan', 'prices'])
            ->findOrFail($id);

        return response()->json(['market' => $market]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:wet_market,palengke,supermarket,grocery,tindahan'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $market = Market::create($validated);

        return response()->json(['market' => $market], 201);
    }

    public function update(Request $request, int $id)
    {
        $market = Market::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:wet_market,palengke,supermarket,grocery,tindahan'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $market->update($validated);

        return response()->json(['market' => $market->fresh()]);
    }

    public function destroy(int $id)
    {
        Market::findOrFail($id)->delete();

        return response()->json(['message' => 'Market deleted.']);
    }

    // Real Claude API + web-search call (~$0.01-0.02/invocation) — same cost profile as
    // the Filament "Refresh via AI" action it replaces.
    public function refreshAi(int $id, PriceIntelligenceService $service)
    {
        $market = Market::findOrFail($id);

        try {
            $count = $service->refreshMarket($market);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Price refresh failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => "Refreshed {$count} prices.", 'count' => $count]);
    }

    // Manual trigger for the same loop the 2am `prices:refresh-ai` schedule
    // runs — lets an admin get fresh prices on demand instead of waiting
    // for the next run. One real Claude API + web-search call per active
    // market, so cost and request time both scale with market count.
    public function refreshAiAll(PriceIntelligenceService $service)
    {
        $markets = Market::where('is_active', true)->get();

        if ($markets->isEmpty()) {
            return response()->json(['message' => 'No active markets found.'], 422);
        }

        $total   = 0;
        $results = [];

        foreach ($markets as $market) {
            try {
                $count   = $service->refreshMarket($market);
                $total  += $count;
                $results[] = ['market' => $market->name, 'count' => $count];
            } catch (\Throwable $e) {
                $results[] = ['market' => $market->name, 'error' => $e->getMessage()];
            }

            // Brief pause between markets to avoid rate-limiting — same as prices:refresh-ai.
            if ($market->isNot($markets->last())) {
                sleep(2);
            }
        }

        return response()->json([
            'message' => "Refreshed {$markets->count()} markets, {$total} prices saved.",
            'total' => $total,
            'results' => $results,
        ]);
    }
}
