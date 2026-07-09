<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityPriceReport extends Model
{
    protected $fillable = [
        'user_id',
        'tindahan_id',
        'market_id',
        'item_name',
        'category',
        'reported_price',
        'unit',
        'barangay',
        'municipality',
        'province',
        'upvotes',
        'downvotes',
        'is_verified',
    ];

    protected $casts = [
        'reported_price' => 'decimal:2',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}
