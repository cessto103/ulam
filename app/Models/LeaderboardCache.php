<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardCache extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'scope_value',
        'xp_total',
        'rank',
        'period_date',
    ];

    protected $casts = [
        'period_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
