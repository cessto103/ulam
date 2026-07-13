<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerPlanPrice extends Model
{
    /** duration slug => days of access */
    public const DURATION_DAYS = [
        '7d' => 7,
        '15d' => 15,
        '1m' => 30,
        '1y' => 365,
    ];

    protected $fillable = [
        'seller_plan_id',
        'duration',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SellerPlan::class, 'seller_plan_id');
    }

    public function days(): int
    {
        return self::DURATION_DAYS[$this->duration] ?? 30;
    }
}
