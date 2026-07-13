<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSession extends Model
{
    protected $fillable = [
        'public_id', 'user_id', 'seller_plan_price_id', 'subscription_id',
        'provider', 'provider_session_id', 'idempotency_key', 'status',
        'amount', 'currency', 'checkout_url', 'expires_at', 'metadata',
    ];

    protected $casts = ['expires_at' => 'datetime', 'metadata' => 'array'];
    protected $hidden = ['idempotency_key'];

    public function user() { return $this->belongsTo(User::class); }
    public function price() { return $this->belongsTo(SellerPlanPrice::class, 'seller_plan_price_id'); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
}
