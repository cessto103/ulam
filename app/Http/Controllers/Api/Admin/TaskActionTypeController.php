<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskActionType;
use Illuminate\Http\Request;

/**
 * Deliberately index()+update() only -- no store()/destroy(). Letting an
 * admin type a brand-new key here would recreate the exact
 * action_type/reason mismatch bug the old hardcoded KNOWN_ACTION_TYPES list
 * was built to prevent (a key that looks valid in the dropdown but isn't
 * actually wired to any real XpService::award() call site). New keys are
 * added by a developer wiring a real call site + a migration/seeder row in
 * the same commit -- this controller only lets admin fix a label typo or
 * retire one from the dropdown via is_active.
 */
class TaskActionTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'action_types' => TaskActionType::orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $type = TaskActionType::findOrFail($id);
        $data = $request->validate([
            'label'     => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $type->update($data);

        return response()->json(['action_type' => $type->fresh()]);
    }
}
