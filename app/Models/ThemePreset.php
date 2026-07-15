<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemePreset extends Model
{
    protected $fillable = ['name', 'slug', 'sections', 'is_active'];

    protected $casts = [
        'sections'  => 'array',
        'is_active' => 'boolean',
    ];
}
