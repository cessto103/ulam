<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Services\UserModerationService;
use Illuminate\Http\Request;

// No store/update — this is a read + moderate surface. Mutation only happens through
// the three actions below, ported from Filament's ListingReportResource row actions.
class ListingReportController extends Controller
{
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
        $report = $this->pendingReport($id);
        $owner = $report->reportable?->user;

        if (! $owner) {
            return response()->json(['message' => 'No owner found for this listing.'], 422);
        }

        $this->moderation->ban($owner, "Reported listing: {$report->reason}", $request->user(), listingReport: $report);

        $this->resolve($report, $request);

        return response()->json(['report' => $report->fresh(), 'message' => "Banned {$owner->name}."]);
    }

    public function deactivateListing(Request $request, int $id)
    {
        $report = $this->pendingReport($id);

        if (! $report->reportable) {
            return response()->json(['message' => 'Listing no longer exists.'], 422);
        }

        $report->reportable->update(['is_active' => false]);
        $this->resolve($report, $request);

        return response()->json(['report' => $report->fresh()]);
    }

    public function dismiss(Request $request, int $id)
    {
        $report = $this->pendingReport($id);
        $report->update([
            'status' => 'dismissed',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json(['report' => $report->fresh()]);
    }

    private function pendingReport(int $id): ListingReport
    {
        $report = ListingReport::with('reportable')->findOrFail($id);

        abort_if($report->status !== 'pending', 422, 'This report has already been resolved.');

        return $report;
    }

    private function resolve(ListingReport $report, Request $request): void
    {
        $report->update([
            'status' => 'actioned',
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);
    }
}
