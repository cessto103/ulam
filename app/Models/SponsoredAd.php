<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsoredAd extends Model
{
    protected $fillable = [
        'product_name',
        'company_name',
        'tagline',
        'description',
        'image_url',
        'link_url',
        'cta_label',
        'amount_paid',
        'payment_received_at',
        'start_date',
        'end_date',
        'is_enabled',
        'show_to_free',
        'show_to_premium',
        'show_in_recipe_feed',
        'show_in_community_feed',
        'contact_name',
        'contact_email',
        'notes',
    ];
    // impressions_count/clicks_count deliberately NOT fillable -- only ever
    // touched via increment() from the mobile-facing impression/click routes.

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_received_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_enabled' => 'boolean',
        'show_to_free' => 'boolean',
        'show_to_premium' => 'boolean',
        'show_in_recipe_feed' => 'boolean',
        'show_in_community_feed' => 'boolean',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
    ];

    /**
     * Mirrors UserStrike::isActive() / User::isRestricted() -- computed live
     * on every read, never a stored/synced status column. Compared as Y-m-d
     * strings (not Carbon lte/gte on the date-cast instances) so the whole
     * end_date day counts as running, not just its literal 00:00:00 instant.
     */
    public function isCurrentlyRunning(): bool
    {
        $today = now()->toDateString();

        return $this->is_enabled
            && $this->start_date?->toDateString() <= $today
            && $this->end_date?->toDateString() >= $today;
    }

    public function displayStatus(): string
    {
        if (! $this->is_enabled) {
            return 'disabled';
        }

        $today = now()->toDateString();

        if ($this->start_date?->toDateString() > $today) {
            return 'scheduled';
        }

        if ($this->end_date?->toDateString() < $today) {
            return 'ended';
        }

        return 'running';
    }
}
