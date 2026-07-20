<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStrike extends Model
{
    const LEVEL_WARNING = 1;
    const LEVEL_RESTRICTION = 2;
    const LEVEL_BAN = 3;

    const LEVELS = [
        self::LEVEL_WARNING => 'warning',
        self::LEVEL_RESTRICTION => 'restriction',
        self::LEVEL_BAN => 'ban',
    ];

    protected $fillable = [
        'user_id',
        'level',
        'reason',
        'content_report_id',
        'listing_report_id',
        'issued_by',
        'expires_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? 'unknown';
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function contentReport()
    {
        return $this->belongsTo(ContentReport::class);
    }

    public function listingReport()
    {
        return $this->belongsTo(ListingReport::class);
    }
}
