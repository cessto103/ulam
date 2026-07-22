<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherPhrase extends Model
{
    protected $fillable = [
        'weather_category',
        'variant_type',
        'phrase_text',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
}
