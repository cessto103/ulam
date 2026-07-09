<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPeriod extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount',
        'total_days',
        'household_size',
        'daily_fare',
        'daily_allowance',
        // daily_food_budget is a stored generated column — DB computes it
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'total_amount'       => 'decimal:2',
        'total_days'         => 'integer',
        'household_size'     => 'integer',
        'daily_fare'         => 'decimal:2',
        'daily_allowance'    => 'decimal:2',
        'daily_food_budget'  => 'decimal:2',
        'start_date'         => 'date',
        'end_date'           => 'date',
        'is_active'          => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyBudgetLog::class);
    }
}
