<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $fillable = [
        'shopping_list_id',
        'name',
        'quantity',
        'unit',
        'needed_note',
        'meal_type',
        'dish_name',
        'est_price',
        'actual_price',
        'is_checked',
        'added_by',
        'checked_by',
        'sort_order',
    ];

    protected $casts = [
        'est_price' => 'float',
        'actual_price' => 'float',
        'is_checked' => 'boolean',
    ];

    public function list()
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
