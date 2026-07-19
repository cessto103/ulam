<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaplePrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaplePriceController extends Controller
{
    public function index()
    {
        return response()->json([
            'staples' => StaplePrice::orderBy('item_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $staple = StaplePrice::create($this->validated($request));

        return response()->json(['staple' => $staple], 201);
    }

    public function update(Request $request, int $id)
    {
        $staple = StaplePrice::findOrFail($id);
        $staple->update($this->validated($request, $id));

        return response()->json(['staple' => $staple->fresh()]);
    }

    public function destroy(int $id)
    {
        StaplePrice::findOrFail($id)->delete();

        return response()->json(['message' => 'Staple deleted.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'item_name' => ['required', 'string', 'max:100', Rule::unique('staple_prices', 'item_name')->ignore($ignoreId)],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
