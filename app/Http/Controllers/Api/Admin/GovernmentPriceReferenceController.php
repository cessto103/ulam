<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentPriceReference;
use Illuminate\Http\Request;

class GovernmentPriceReferenceController extends Controller
{
    public function index(Request $request)
    {
        $query = GovernmentPriceReference::query();

        if ($request->filled('search')) {
            $query->where('item_name', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }

        if ($request->filled('region')) {
            $query->where('region', $request->string('region'));
        }

        return response()->json(
            $query->orderByDesc('bulletin_date')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        return response()->json(['reference' => GovernmentPriceReference::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $reference = GovernmentPriceReference::create($validated);

        return response()->json(['reference' => $reference], 201);
    }

    public function update(Request $request, int $id)
    {
        $reference = GovernmentPriceReference::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));

        $reference->update($validated);

        return response()->json(['reference' => $reference->fresh()]);
    }

    public function destroy(int $id)
    {
        GovernmentPriceReference::findOrFail($id)->delete();

        return response()->json(['message' => 'Reference deleted.']);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'source' => [$req, 'in:da_bantay_presyo,dti_srp'],
            'item_name' => [$req, 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'price_min' => [$req, 'numeric', 'min:0'],
            'price_max' => [$req, 'numeric', 'min:0'],
            'unit' => [$req, 'string', 'max:30'],
            'region' => ['nullable', 'string', 'max:50'],
            'bulletin_date' => ['nullable', 'date'],
            'source_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
