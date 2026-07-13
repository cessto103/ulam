<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'post_type',
        'body',
        'images',
        'barangay',
        'municipality',
        'budget_amount',
        'serving_size',
        'is_sponsored',
        'tindahan_id',
        'puso_count',
        'comments_count',
        'recipe_id',
    ];

    protected $casts = [
        'images' => 'array',
        'budget_amount' => 'decimal:2',
        'is_sponsored' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class)->whereNull('parent_id');
    }

    public function saves()
    {
        return $this->hasMany(PostSave::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }

    public function contentViews()
    {
        return $this->morphMany(ContentView::class, 'viewable');
    }
}
