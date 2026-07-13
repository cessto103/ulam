<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdBoost extends Model
{
    protected $fillable = [
        'user_id',
        'boostable_type',
        'boostable_id',
        'duration',
        'duration_days',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'status',
        'rejected_reason',
        'reviewed_at',
        'activated_by',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function boostable()
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('expires_at', '>', now());
    }
}
