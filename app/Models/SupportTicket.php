<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    public const CATEGORIES = ['payment', 'subscription', 'store', 'account', 'bug', 'other'];

    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'status',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportTicketMessage::class)->latestOfMany();
    }
}
