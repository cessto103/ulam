<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'barangay',
        'municipality',
        'province',
        'region',
        'latitude',
        'longitude',
        'is_active',
        'source',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tindahan()
    {
        return $this->hasMany(Tindahan::class);
    }

    public function prices()
    {
        return $this->hasMany(MarketPrice::class);
    }

    public function reports()
    {
        return $this->morphMany(ListingReport::class, 'reportable');
    }
}
