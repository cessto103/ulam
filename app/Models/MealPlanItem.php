<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlanItem extends Model
{
    protected $fillable = [
        'meal_plan_id',
        'meal_type',
        'dish_name',
        'description',
        'estimated_cost',
        'servings',
        'recipe_id',
        'sort_order',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
    ];

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredients()
    {
        return $this->hasMany(MealPlanIngredient::class);
    }
}
