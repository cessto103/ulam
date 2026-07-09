<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityPriceReport;
use Illuminate\Http\Request;

class CommunityPriceReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CommunityPriceReport::with(['user:id,name', 'tindahan:id,name', 'market:id,name']);

        if ($request->filled('search')) {
            $query->where('item_name', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $report = CommunityPriceReport::with(['user:id,name', 'tindahan:id,name', 'market:id,name'])->findOrFail($id);

        return response()->json(['report' => $report]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['user_id'] = $request->user()->id;

        $report = CommunityPriceReport::create($validated);

        return response()->json(['report' => $report], 201);
    }

    public function update(Request $request, int $id)
    {
        $report = CommunityPriceReport::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));

        $report->update($validated);

        return response()->json(['report' => $report->fresh()]);
    }

    public function destroy(int $id)
    {
        CommunityPriceReport::findOrFail($id)->delete();

        return response()->json(['message' => 'Report deleted.']);
    }

    // One-way toggle — matches Filament (no "unverify" action exists).
    public function verify(int $id)
    {
        $report = CommunityPriceReport::findOrFail($id);
        $report->update(['is_verified' => true]);

        return response()->json(['report' => $report->fresh()]);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'tindahan_id' => ['nullable', 'integer', 'exists:tindahan,id'],
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'item_name' => [$req, 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'reported_price' => [$req, 'numeric', 'min:0'],
            'unit' => [$req, 'string', 'max:30'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'is_verified' => ['sometimes', 'boolean'],
        ];
    }
}
