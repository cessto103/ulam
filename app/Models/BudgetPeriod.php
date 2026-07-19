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
        'custom_expenses',
        // daily_food_budget is computed in BudgetController::setup() —
        // total_amount/total_days minus the sum of custom_expenses, since a
        // same-row generated column can't sum a JSON list.
        'daily_food_budget',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'total_amount'       => 'decimal:2',
        'total_days'         => 'integer',
        'household_size'     => 'integer',
        'custom_expenses'    => 'array',
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
