<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRewardTier extends Model
{
    protected $fillable = [
        'user_id',
        'reward_tier_id',
        'earned_at',
        'redeemed_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rewardTier()
    {
        return $this->belongsTo(RewardTier::class);
    }
}
