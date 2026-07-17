<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DailyTaskController extends Controller
{
    public function index()
    {
        return response()->json([
            'tasks' => DailyTask::orderBy('frequency')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $task = DailyTask::create($data);

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, int $id)
    {
        $task = DailyTask::findOrFail($id);
        $task->update($this->validated($request));

        return response()->json(['task' => $task->fresh()]);
    }

    public function destroy(int $id)
    {
        DailyTask::findOrFail($id)->delete();

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
