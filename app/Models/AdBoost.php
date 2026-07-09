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
        'amount_paid',
        'status',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function boostable()
    {
        return $this->morphTo();
    }
}
