<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'image_url',
        'image_urls',
        'collage_style',
        'gradient_key',
        'font_key',
        'youtube_url',
        'source',
        'budget_tag',
        'estimated_cost',
        'servings',
        'prep_time_minutes',
        'cook_time_minutes',
        'difficulty',
        'tags',
        'dietary_flags',
        'steps',
        'tips',
        'is_premium_only',
        'is_published',
        'save_count',
        'share_count',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'tags' => 'array',
        'dietary_flags' => 'array',
        'steps' => 'array',
        'tips' => 'array',
        'image_urls' => 'array',
        'is_premium_only' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    public function savedBy()
    {
        return $this->hasMany(RecipeBook::class);
    }
}
