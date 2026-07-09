<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'name',
        'quantity',
        'unit',
        'estimated_price',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
