<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerPlan extends Model
{
    public const FREE_SLUG = 'free';

    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'max_stores',
        'max_items_per_store',
        'sort',
        'is_active',
    ];

    protected $casts = [
        'max_stores' => 'integer',
        'max_items_per_store' => 'integer',
        'sort' => 'integer',
        'is_active' => 'boolean',
    ];

    public function prices()
    {
        return $this->hasMany(SellerPlanPrice::class)->orderByRaw(
            "FIELD(duration, '7d', '15d', '1m', '1y')"
        );
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'seller_plan_features')
            ->withPivot('value')->withTimestamps();
    }

    public static function free(): self
    {
        return static::where('slug', self::FREE_SLUG)->firstOrFail();
    }
}
