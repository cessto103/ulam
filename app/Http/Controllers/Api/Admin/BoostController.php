<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdBoost;
use App\Models\Recipe;
use App\Services\BoostService;
use Illuminate\Http\Request;

class BoostController extends Controller
{
    public function __construct(private BoostService $service)
    {
    }

    /** GET /admin/boosts — filterable list + queue counts. */
    public function index(Request $request)
    {
        $query = AdBoost::with(['user:id,name,username,email', 'boostable']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('payment_reference', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $page = $query->orderByRaw("FIELD(status, 'pending') DESC")
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        $page->getCollection()->transform(function (AdBoost $boost) {
            $boost->target = $boost->boostable_type === Recipe::class ? 'recipe' : 'tindahan';
            $boost->target_name = $boost->boostable->title ?? $boost->boostable->name ?? null;
            unset($boost->boostable);
            return $boost;
        });

        return response()->json(array_merge($page->toArray(), [
            'counts' => [
                'pending' => AdBoost::where('status', 'pending')->count(),
                'active' => AdBoost::active()->count(),
            ],
        ]));
    }

    /** POST /admin/boosts/{id}/approve */
    public function approve(Request $request, int $id)
    {
        $boost = AdBoost::findOrFail($id);

        if ($boost->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be approved.'], 422);
        }

        $boost = $this->service->approve($boost, $request->user());

        return response()->json(['message' => 'Boost activated.', 'boost' => $boost]);
    }

    /** POST /admin/boosts/{id}/reject */
    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $boost = AdBoost::findOrFail($id);

        if ($boost->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be rejected.'], 422);
        }

        $boost = $this->service->reject($boost, $request->user(), $validated['reason']);

        return response()->json(['message' => 'Submission rejected.', 'boost' => $boost]);
    }
}
