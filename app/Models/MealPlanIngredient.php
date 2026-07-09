<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealPlanIngredient extends Model
{
    protected $fillable = [
        'meal_plan_item_id',
        'name',
        'quantity',
        'unit',
        'estimated_price',
        'notes',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
    ];

    public function mealPlanItem()
    {
        return $this->belongsTo(MealPlanItem::class);
    }
}
