<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardTier extends Model
{
    /** All reward types the schema supports. */
    const REWARD_TYPES = ['premium_days', 'booster_credit', 'store_boost_credit', 'badge', 'discount_code'];

    /** Reward types the admin can actually choose today -- discount_code isn't implemented yet. */
    const SELECTABLE_REWARD_TYPES = ['premium_days', 'booster_credit', 'store_boost_credit', 'badge'];

    protected $fillable = [
        'title',
        'description',
        'icon',
        'xp_threshold',
        'reward_type',
        'reward_value',
        'is_active',
    ];

    protected $casts = [
        'xp_threshold' => 'integer',
        'reward_value' => 'integer',
        'is_active' => 'boolean',
    ];

    public function requiredTasks()
    {
        return $this->belongsToMany(Task::class, 'reward_tier_tasks');
    }

    public function userRewardTiers()
    {
        return $this->hasMany(UserRewardTier::class);
    }
}
