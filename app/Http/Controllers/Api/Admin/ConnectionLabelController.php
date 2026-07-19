<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectionLabel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConnectionLabelController extends Controller
{
    public function index()
    {
        return response()->json([
            'labels' => ConnectionLabel::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $label = ConnectionLabel::create($this->validated($request));

        return response()->json(['label' => $label], 201);
    }

    public function update(Request $request, int $id)
    {
        $label = ConnectionLabel::findOrFail($id);
        $label->update($this->validated($request, $id));

        return response()->json(['label' => $label->fresh()]);
    }

    public function destroy(int $id)
    {
        // The label FKs on connections are nullOnDelete, so deleting a label
        // simply clears it from any connection that used it.
        ConnectionLabel::findOrFail($id)->delete();

        return response()->json(['message' => 'Label deleted.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('connection_labels', 'name')->ignore($ignoreId)],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
