<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'icon',
        'xp_reward',
        'category',
        'condition',
        'is_active',
    ];

    protected $casts = [
        'condition' => 'array',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('earned_at')
            ->withTimestamps();
    }
}
