<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\ResolvesModerationReports;
use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Services\UserModerationService;
use Illuminate\Http\Request;

// No store/update — this is a read + moderate surface. Mutation only happens through
// the three actions below, ported from Filament's ListingReportResource row actions.
class ListingReportController extends Controller
{
    use ResolvesModerationReports;

    public function __construct(private UserModerationService $moderation)
    {
    }

    public function index(Request $request)
    {
        $query = ListingReport::with(['reporter:id,name', 'resolvedBy:id,name', 'reportable']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('reportable_type')) {
            $type = $request->string('reportable_type') === 'market' ? \App\Models\Market::class : \App\Models\Tindahan::class;
            $query->where('reportable_type', $type);
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                    ->orWhereHas('reporter', fn ($u) => $u->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%"))
                    ->orWhereHasMorph('reportable', [\App\Models\Market::class, \App\Models\Tindahan::class], fn ($r) => $r->where('name', 'like', "%{$term}%"));
            });
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $report = ListingReport::with(['reporter:id,name', 'resolvedBy:id,name', 'reportable'])->findOrFail($id);

        return response()->json(['report' => $report]);
    }

    public function destroy(int $id)
    {
        ListingReport::findOrFail($id)->delete();

        return response()->json(['message' => 'Report deleted.']);
    }

    public function banOwner(Request $request, int $id)
    {
        $report = $this->pendingReportOrFail(ListingReport::class, $id, ['reportable']);
        $owner = $report->reportable?->user;

        if (! $owner) {
            return response()->json(['message' => 'No owner found for this listing.'], 422);
        }

        $this->moderation->ban($owner, "Reported listing: {$report->reason}", $request->user(), listingReport: $report);

        $this->resolveReport($report, $request);

        return response()->json(['report' => $report->fresh(), 'message' => "Banned {$owner->name}."]);
    }

    public function deactivateListing(Request $request, int $id)
    {
        $report = $this->pendingReportOrFail(ListingReport::class, $id, ['reportable']);

        if (! $report->reportable) {
            return response()->json(['message' => 'Listing no longer exists.'], 422);
        }

        $report->reportable->update(['is_active' => false]);
        $this->resolveReport($report, $request);

        return response()->json(['report' => $report->fresh()]);
    }

    public function dismiss(Request $request, int $id)
    {
        $report = $this->pendingReportOrFail(ListingReport::class, $id, ['reportable']);
        $this->resolveReport($report, $request, 'dismissed');

        return response()->json(['report' => $report->fresh()]);
    }
}
