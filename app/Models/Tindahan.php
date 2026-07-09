<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tindahan extends Model
{
    protected $table = 'tindahan';

    protected $fillable = [
        'user_id',
        'market_id',
        'name',
        'description',
        'type',
        'barangay',
        'municipality',
        'province',
        'region',
        'latitude',
        'longitude',
        'contact_number',
        'store_hours',
        'gcash_number',
        'is_active',
        'is_verified',
        'logo',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'store_hours' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
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
