<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    protected $fillable = [
        'user_id',
        'plan_date',
        'source',
        'budget_period_id',
        'total_estimated_cost',
        'ai_prompt_tokens',
        'ai_completion_tokens',
        'notes',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'total_estimated_cost' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgetPeriod()
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function items()
    {
        return $this->hasMany(MealPlanItem::class);
    }
}
