<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tindahan;
use Illuminate\Http\Request;

class TindahanController extends Controller
{
    public function index(Request $request)
    {
        $query = Tindahan::with('market:id,name');

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('municipality', 'like', "%{$q}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $tindahan = Tindahan::with('market:id,name')->findOrFail($id);

        return response()->json(['tindahan' => $tindahan]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $tindahan = Tindahan::create($validated);

        return response()->json(['tindahan' => $tindahan], 201);
    }

    public function update(Request $request, int $id)
    {
        $tindahan = Tindahan::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));
        $tindahan->update($validated);

        return response()->json(['tindahan' => $tindahan->fresh()]);
    }

    public function destroy(int $id)
    {
        Tindahan::findOrFail($id)->delete();

        return response()->json(['message' => 'Store deleted.']);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'name' => [$req, 'string', 'max:255'],
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'gcash_number' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
        ];
    }
}
