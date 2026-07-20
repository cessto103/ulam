<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTask extends Model
{
    /** Sentinel period_date for 'once' (lifetime) tasks -- not a literal
     *  NULL, since MySQL treats multiple NULLs in a unique index as
     *  distinct, which would let a lifetime task be earned more than once. */
    const LIFETIME_PERIOD = '1970-01-01';

    protected $fillable = [
        'user_id',
        'task_id',
        'period_date',
        'progress_count',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'period_date'  => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
