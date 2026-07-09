<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDailyTask extends Model
{
    protected $fillable = [
        'user_id',
        'daily_task_id',
        'task_date',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'task_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(DailyTask::class, 'daily_task_id');
    }
}
