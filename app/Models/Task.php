<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'title_en',
        'description',
        'description_en',
        'icon',
        'xp_reward',
        'action_type',
        'frequency',
        'target_count',
        'tier',
        'tier_group',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userTasks()
    {
        return $this->hasMany(UserTask::class);
    }

    public function rewardTiers()
    {
        return $this->belongsToMany(RewardTier::class, 'reward_tier_tasks');
    }

    /**
     * Mr./Ms. Palengke is the only task whose title depends on the user, so
     * it's a special case here rather than new schema columns for every
     * task -- promote to per-gender title columns if a second gendered
     * task is ever added.
     */
    public static function displayTitle(self $task, ?string $gender, string $lang = 'tl'): string
    {
        if ($task->tier_group === 'palengke_pro') {
            return match ($gender) {
                'male'   => 'Mr. Palengke',
                'female' => 'Ms. Palengke',
                default  => 'Palengke Pro',
            };
        }

        if ($lang === 'en') {
            return $task->title_en ?: $task->title;
        }

        return $task->title;
    }
}
