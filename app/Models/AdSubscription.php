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
        'duration',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'status',
        'rejected_reason',
        'reviewed_at',
        'refunded_at',
        'starts_at',
        'expires_at',
        'renewal_notified',
        'activated_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'refunded_at' => 'datetime',
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

    public function sellerPlan()
    {
        return $this->belongsTo(SellerPlan::class, 'plan', 'slug');
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /** Currently-running seller subscriptions. */
    public function scopeActiveSeller($query)
    {
        return $query->where('type', 'tindahan_listing')
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }
}
