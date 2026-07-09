<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'plan',
        'role',
        'banned_at',
        'ban_reason',
        'premium_expires_at',
        'household_size',
        'barangay',
        'municipality',
        'province',
        'region',
        'latitude',
        'longitude',
        'dietary_preferences',
        'xp',
        'level',
        'streak_days',
        'last_active_date',
        'ai_meal_plans_used_this_month',
        'ai_quota_reset_date',
        'onboarding_completed',
        'push_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'premium_expires_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_active_date' => 'date',
            'ai_quota_reset_date' => 'date',
            'dietary_preferences' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' && !$this->banned_at;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBanned(): bool
    {
        return (bool) $this->banned_at;
    }

    public function isPremium(): bool
    {
        return $this->plan === 'premium'
            && $this->premium_expires_at
            && $this->premium_expires_at->isFuture();
    }

    public function canGenerateAiMealPlan(): bool
    {
        if ($this->isPremium()) {
            return true;
        }

        $this->resetQuotaIfNewMonth();
        return $this->ai_meal_plans_used_this_month < 3;
    }

    public function resetQuotaIfNewMonth(): void
    {
        $now = now()->startOfMonth()->toDateString();
        if ($this->ai_quota_reset_date?->format('Y-m-d') !== $now) {
            $this->update([
                'ai_meal_plans_used_this_month' => 0,
                'ai_quota_reset_date' => $now,
            ]);
        }
    }

    public function budgetPeriods()
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    public function activeBudget()
    {
        $today = today()->toDateString();
        return $this->hasOne(BudgetPeriod::class)
            ->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    // Alias used by MealPlanController
    public function getCurrentBudgetAttribute()
    {
        return $this->activeBudget()->first();
    }

    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function connections()
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function recipeBook()
    {
        return $this->hasMany(RecipeBook::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function xpLogs()
    {
        return $this->hasMany(XpLog::class);
    }
}
