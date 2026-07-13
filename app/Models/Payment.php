<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'checkout_session_id',
        'provider',
        'provider_payment_id',
        'plan_type',
        'amount',
        'currency',
        'status',
        'failure_code',
        'failure_message',
        'paid_at',
        'refunded_at',
        'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function checkoutSession() { return $this->belongsTo(CheckoutSession::class); }
}
