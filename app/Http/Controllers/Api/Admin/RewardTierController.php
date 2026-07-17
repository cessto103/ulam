<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardTier;
use Illuminate\Http\Request;

class RewardTierController extends Controller
{
    public function index()
    {
        return response()->json([
            'tiers' => RewardTier::orderBy('xp_threshold')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $tier = RewardTier::create($this->validated($request));

        return response()->json(['tier' => $tier], 201);
    }

    public function update(Request $request, int $id)
    {
        $tier = RewardTier::findOrFail($id);
        $tier->update($this->validated($request));

        return response()->json(['tier' => $tier->fresh()]);
    }

    public function destroy(int $id)
    {
        RewardTier::findOrFail($id)->delete();

        return response()->json(['message' => 'Reward tier deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:10'],
            'xp_threshold' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
