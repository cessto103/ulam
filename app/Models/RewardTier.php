<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardTier extends Model
{
    protected $fillable = [
        'title',
        'description',
        'icon',
        'xp_threshold',
        'is_active',
    ];

    protected $casts = [
        'xp_threshold' => 'integer',
        'is_active' => 'boolean',
    ];
}
