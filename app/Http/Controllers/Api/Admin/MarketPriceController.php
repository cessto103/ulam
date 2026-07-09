<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketPrice;
use Illuminate\Http\Request;

class MarketPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketPrice::with(['market:id,name', 'tindahan:id,name']);

        if ($request->filled('search')) {
            $query->where('item_name', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->integer('market_id'));
        }

        if ($request->filled('tindahan_id')) {
            $query->where('tindahan_id', $request->integer('tindahan_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        return response()->json(
            $query->orderByDesc('updated_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $price = MarketPrice::with(['market:id,name', 'tindahan:id,name'])->findOrFail($id);

        return response()->json(['price' => $price]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['last_updated_by'] = $request->user()->id;

        $price = MarketPrice::create($validated);

        return response()->json(['price' => $price], 201);
    }

    public function update(Request $request, int $id)
    {
        $price = MarketPrice::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));
        $validated['last_updated_by'] = $request->user()->id;

        $price->update($validated);

        return response()->json(['price' => $price->fresh()]);
    }

    public function destroy(int $id)
    {
        MarketPrice::findOrFail($id)->delete();

        return response()->json(['message' => 'Price deleted.']);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'tindahan_id' => ['nullable', 'integer', 'exists:tindahan,id'],
            'item_name' => [$req, 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'price_per_unit' => [$req, 'numeric', 'min:0'],
            'unit' => [$req, 'string', 'max:30'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
