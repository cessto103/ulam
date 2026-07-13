<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostOption extends Model
{
    protected $fillable = [
        'target',
        'duration_days',
        'price',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
}
