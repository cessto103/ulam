<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const ACTIVE_STATUSES = ['active', 'grace_period'];

    protected $fillable = [
        'user_id', 'seller_plan_id', 'seller_plan_price_id', 'provider',
        'provider_subscription_id', 'status', 'current_period_start',
        'current_period_end', 'grace_ends_at', 'cancel_at_period_end',
        'cancelled_at', 'suspended_at', 'metadata',
    ];

    protected $casts = [
        'current_period_start' => 'datetime', 'current_period_end' => 'datetime',
        'grace_ends_at' => 'datetime', 'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime', 'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(SellerPlan::class, 'seller_plan_id'); }
    public function price() { return $this->belongsTo(SellerPlanPrice::class, 'seller_plan_price_id'); }

    public function scopeEntitled($query)
    {
        return $query->where(function ($q) {
            $q->where(fn ($active) => $active->where('status', 'active')->where('current_period_end', '>', now()))
                ->orWhere(fn ($grace) => $grace->where('status', 'grace_period')->where('grace_ends_at', '>', now()));
        });
    }
}
