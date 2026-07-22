<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherForecastCache extends Model
{
    protected $table = 'weather_forecast_cache';

    protected $fillable = [
        'bucket_key',
        'forecast_date',
        'weather_category',
        'consecutive_rain_days',
        'raw_response',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'consecutive_rain_days' => 'integer',
        'raw_response' => 'array',
    ];
}
