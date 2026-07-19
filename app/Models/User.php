<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'secondary_email',
        'secondary_email_verified_at',
        'secondary_email_otp',
        'secondary_email_otp_expires_at',
        'email_verified_at',
        'email_verification_otp',
        'email_verification_otp_expires_at',
        'password_reset_otp',
        'password_reset_otp_expires_at',
        'twofa_secret',
        'twofa_enabled_at',
        'twofa_last_ts',
        'password',
        'avatar',
        'bio',
        'plan',
        'role',
        'banned_at',
        'ban_reason',
        'premium_expires_at',
        'premium_source',
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
        'secondary_email_otp',
        'secondary_email_otp_expires_at',
        'email_verification_otp',
        'email_verification_otp_expires_at',
        'password_reset_otp',
        'password_reset_otp_expires_at',
        'twofa_secret',
        'twofa_last_ts',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_otp_expires_at' => 'datetime',
            'secondary_email_verified_at' => 'datetime',
            'secondary_email_otp_expires_at' => 'datetime',
            'password_reset_otp_expires_at' => 'datetime',
            'twofa_secret' => 'encrypted',
            'twofa_enabled_at' => 'datetime',
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
        // AI meal plan generation is Premium-only — each generation is a real,
        // billed Anthropic API call, so there's no free-tier allowance.
        return $this->isPremium();
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

    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_id');
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

    public function tindahan()
    {
        return $this->hasMany(Tindahan::class);
    }

    public function sellerSubscriptions()
    {
        return $this->hasMany(AdSubscription::class)->where('type', 'tindahan_listing');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function contentViews()
    {
        return $this->hasMany(ContentView::class);
    }

    /** The currently-running paid seller subscription, if any. */
    public function activeSellerSubscription(): ?AdSubscription
    {
        return $this->sellerSubscriptions()
            ->activeSeller()
            ->orderByDesc('expires_at')
            ->first();
    }

    /** The seller plan in force — falls back to the 'free' catalog row. */
    public function sellerPlan(): SellerPlan
    {
        $subscription = $this->subscriptions()->entitled()->latest('current_period_end')->first();
        if ($subscription?->plan) {
            return $subscription->plan;
        }

        $active = $this->activeSellerSubscription();

        if ($active) {
            $plan = SellerPlan::where('slug', $active->plan)->first();
            if ($plan) {
                return $plan;
            }
        }

        return SellerPlan::free();
    }
}
