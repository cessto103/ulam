<?php

namespace App\Http\Controllers\Api\Admin\Concerns;

use Illuminate\Http\Request;

/**
 * Shared by ListingReportController and ContentReportController -- both are
 * "pending -> actioned|dismissed" review queues with the same guard/resolve
 * shape, just against different report models.
 */
trait ResolvesModerationReports
{
    private function pendingReportOrFail(string $modelClass, int $id, array $with = [])
    {
        $report = $modelClass::with($with)->findOrFail($id);

        abort_if($report->status !== 'pending', 422, 'This report has already been resolved.');

        return $report;
    }

    private function resolveReport($report, Request $request, string $status = 'actioned'): void
    {
        $report->update([
            'status' => $status,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);
    }
}
