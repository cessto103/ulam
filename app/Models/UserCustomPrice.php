<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCustomPrice extends Model
{
    protected $fillable = [
        'user_id',
        'item_name',
        'price_per_unit',
        'unit',
        'tindahan_id',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }
}
