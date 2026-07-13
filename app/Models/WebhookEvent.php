<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = ['provider', 'provider_event_id', 'event_type', 'livemode', 'status', 'payload', 'processed_at', 'error'];
    protected $casts = ['livemode' => 'boolean', 'payload' => 'array', 'processed_at' => 'datetime'];
}
