<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardTier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RewardTierController extends Controller
{
    public function index()
    {
        return response()->json([
            'tiers' => RewardTier::with('requiredTasks:id,title,icon')
                ->orderByRaw('xp_threshold IS NULL, xp_threshold')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $tier = RewardTier::create($data);
        $tier->requiredTasks()->sync($data['required_task_ids'] ?? []);

        return response()->json(['tier' => $tier->fresh('requiredTasks')], 201);
    }

    public function update(Request $request, int $id)
    {
        $tier = RewardTier::findOrFail($id);
        $data = $this->validated($request);

        $tier->update($data);
        $tier->requiredTasks()->sync($data['required_task_ids'] ?? []);

        return response()->json(['tier' => $tier->fresh('requiredTasks')]);
    }

    public function destroy(int $id)
    {
        RewardTier::findOrFail($id)->delete();

        return response()->json(['message' => 'Reward tier deleted.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:10'],
            'xp_threshold' => ['nullable', 'integer', 'min:0'],
            'reward_type' => ['required', Rule::in(RewardTier::SELECTABLE_REWARD_TYPES)],
            'reward_value' => [
                Rule::requiredIf(in_array($request->input('reward_type'), ['premium_days', 'booster_credit', 'store_boost_credit'])),
                'nullable', 'integer', 'min:1',
            ],
            'required_task_ids' => ['sometimes', 'array'],
            'required_task_ids.*' => ['integer', 'exists:tasks,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (empty($data['required_task_ids']) && ($data['xp_threshold'] ?? null) === null) {
            abort(422, 'A reward tier needs required tasks, an XP threshold, or both.');
        }

        if ($data['reward_type'] === 'badge') {
            $data['reward_value'] = null;
        }

        return $data;
    }
}
