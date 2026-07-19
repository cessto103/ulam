<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListShare extends Model
{
    protected $fillable = [
        'shopping_list_id',
        'user_id',
    ];

    public function list()
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
