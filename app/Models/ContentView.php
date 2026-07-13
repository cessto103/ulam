<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'user_id',
        'viewed_date',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_date' => 'date',
        'viewed_at' => 'datetime',
    ];

    public function viewable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a view, deduped per (user, content, day) and excluding self-views.
     * Silently no-ops on the race-condition duplicate insert.
     */
    public static function log(Model $viewable, User $viewer, ?int $ownerId): void
    {
        if ($ownerId !== null && $ownerId === $viewer->id) {
            return;
        }

        try {
            static::create([
                'viewable_type' => $viewable->getMorphClass(),
                'viewable_id'   => $viewable->getKey(),
                'user_id'       => $viewer->id,
                'viewed_date'   => now()->toDateString(),
                'viewed_at'     => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (! str_contains($e->getMessage(), 'content_views_unique_per_day')) {
                throw $e;
            }
        }
    }
}
