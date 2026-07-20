<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json([
            'tasks' => Task::orderBy('tier_group')->orderBy('target_count')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        $task = Task::create($data);

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, int $id)
    {
        $task = Task::findOrFail($id);
        $task->update($this->validated($request, $id));

        return response()->json(['task' => $task->fresh()]);
    }

    public function destroy(int $id)
    {
        Task::findOrFail($id)->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'title_en'        => ['nullable', 'string', 'max:255'],
            'description'     => ['required', 'string', 'max:1000'],
            'description_en'  => ['nullable', 'string', 'max:1000'],
            'icon'            => ['nullable', 'string', 'max:10'],
            'xp_reward'       => ['required', 'integer', 'min:0', 'max:65535'],
            'action_type'     => ['nullable', 'string', 'exists:task_action_types,key'],
            'frequency'       => ['required', 'in:daily,weekly,monthly,once'],
            'target_count'    => ['required', 'integer', 'min:1'],
            'tier'            => ['nullable', 'in:bronze,silver,gold,diamond'],
            'tier_group'      => ['nullable', 'string', 'max:50'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        // Tiers only make sense for lifetime tasks -- a daily task resets
        // every period, so "bronze/silver/gold/diamond" has no meaning
        // there (that would be a streak-of-completions feature, out of
        // scope here).
        if ($data['frequency'] !== 'once') {
            $data['tier'] = null;
            $data['tier_group'] = null;
        }

        if (! empty($data['tier_group']) && ! empty($data['tier'])) {
            $request->validate([
                'tier_group' => [Rule::unique('tasks', 'tier_group')->where('tier', $data['tier'])->ignore($ignoreId)],
            ]);
        }

        return $data;
    }
}
