<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'tindahan_id',
        'type',
        'plan',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'status',
        'starts_at',
        'expires_at',
        'renewal_notified',
        'activated_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_notified' => 'boolean',
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
