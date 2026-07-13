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
        'photo',
        'cover_photo',
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
        'hidden_by_plan',
        'logo',
        'average_rating',
        'ratings_count',
        'comments_count',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'hidden_by_plan' => 'boolean',
        'store_hours' => 'array',
        'average_rating' => 'float',
    ];

    /** Visible to buyers: owner/admin hasn't deactivated it AND the owner's plan covers it. */
    public function scopePubliclyVisible($query)
    {
        return $query->where('is_active', true)->where('hidden_by_plan', false);
    }

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

    public function ratings()
    {
        return $this->hasMany(TindahanRating::class);
    }

    public function comments()
    {
        return $this->hasMany(TindahanComment::class);
    }
}
