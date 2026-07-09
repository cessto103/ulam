<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GovernmentPriceReference extends Model
{
    protected $fillable = [
        'source',
        'item_name',
        'category',
        'price_min',
        'price_max',
        'unit',
        'region',
        'bulletin_date',
        'source_note',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'bulletin_date' => 'date',
    ];
}
