<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_payment_id',
        'plan_type',
        'amount',
        'currency',
        'status',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
