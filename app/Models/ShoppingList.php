<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    protected $fillable = [
        'owner_id',
        'type',
        'title',
        'list_date',
        'meal_plan_id',
        'source_recipe_id',
        'status',
        'completed_at',
        'total_spent',
    ];

    protected $casts = [
        'list_date' => 'date',
        'completed_at' => 'datetime',
        'total_spent' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items()
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function shares()
    {
        return $this->hasMany(ShoppingListShare::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'shopping_list_shares');
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    /** Owner or share recipient — the people allowed to see/edit this list. */
    public function isParticipant(int $userId): bool
    {
        return $this->isOwner($userId)
            || $this->shares()->where('user_id', $userId)->exists();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Cash to bring: every item, actual price if entered else estimate. */
    public function allTotal(): float
    {
        return round($this->items->sum(fn ($i) => (float) ($i->actual_price ?? $i->est_price)), 2);
    }

    /** Actually spent: checked (= bought) items only. */
    public function boughtTotal(): float
    {
        return round(
            $this->items->where('is_checked', true)->sum(fn ($i) => (float) ($i->actual_price ?? $i->est_price)),
            2
        );
    }
}
