<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaplePrice extends Model
{
    protected $fillable = [
        'item_name',
        'unit',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
