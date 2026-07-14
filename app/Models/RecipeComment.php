<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeComment extends Model
{
    protected $fillable = ['user_id', 'recipe_id', 'parent_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function replies()
    {
        return $this->hasMany(RecipeComment::class, 'parent_id');
    }
}
