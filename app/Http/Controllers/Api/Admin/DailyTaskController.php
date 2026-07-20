<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Compatibility shim for the existing admin "Daily & Weekly Tasks" page --
 * repointed to the unified Task model, scoped to daily/weekly only, so the
 * old admin UI keeps working unmodified until Phase 2 of the gamification
 * revamp replaces this with the full Tasks CRUD (all 4 frequencies, tiers).
 */
class DailyTaskController extends Controller
{
    public function index()
    {
        return response()->json([
            'tasks' => Task::whereIn('frequency', ['daily', 'weekly'])
                ->orderBy('frequency')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        $data['description_en'] = null;
        $data['title_en'] = null;

        $task = Task::create($data);

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, int $id)
    {
        $task = Task::whereIn('frequency', ['daily', 'weekly'])->findOrFail($id);
        $task->update($this->validated($request));

        return response()->json(['task' => $task->fresh()]);
    }

    public function destroy(int $id)
    {
        Task::whereIn('frequency', ['daily', 'weekly'])->findOrFail($id)->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:10'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:65535'],
            'action_type' => ['required', 'string', 'max:50'],
            'frequency' => ['required', 'in:daily,weekly'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
