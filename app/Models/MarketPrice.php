<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    protected $fillable = [
        'tindahan_id',
        'market_id',
        'item_name',
        'category',
        'price_per_unit',
        'unit',
        'photo',
        'is_available',
        'last_updated_by',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
