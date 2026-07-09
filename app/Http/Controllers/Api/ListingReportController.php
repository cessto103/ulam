<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListingReport;
use App\Models\Market;
use App\Models\Tindahan;
use Illuminate\Http\Request;

class ListingReportController extends Controller
{
    private const TYPE_MAP = [
        'market' => Market::class,
        'tindahan' => Tindahan::class,
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reportable_type' => ['required', 'string', 'in:market,tindahan'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $modelClass = self::TYPE_MAP[$validated['reportable_type']];
        $reportable = $modelClass::find($validated['reportable_id']);

        if (!$reportable) {
            return response()->json(['message' => 'Listing not found.'], 404);
        }

        $report = ListingReport::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $reportable->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json(['report' => $report], 201);
    }
}
