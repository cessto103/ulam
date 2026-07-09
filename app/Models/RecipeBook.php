<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeBook extends Model
{
    protected $table    = 'recipe_book';
    protected $fillable = ['user_id', 'recipe_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
