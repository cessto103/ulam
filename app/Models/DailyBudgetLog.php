<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyBudgetLog extends Model
{
    protected $fillable = [
        'user_id',
        'budget_period_id',
        'log_date',
        'budgeted_amount',
        'actual_spent',
        // saved_amount is a stored generated column — DB computes it
        'expense_breakdown',
        'notes',
    ];

    protected $casts = [
        'log_date'         => 'date',
        'budgeted_amount'  => 'decimal:2',
        'actual_spent'     => 'decimal:2',
        'saved_amount'     => 'decimal:2',
        'expense_breakdown' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgetPeriod()
    {
        return $this->belongsTo(BudgetPeriod::class);
    }
}
