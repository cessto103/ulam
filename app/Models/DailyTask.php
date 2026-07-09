<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'xp_reward',
        'action_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function userTasks()
    {
        return $this->hasMany(UserDailyTask::class);
    }
}
