<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = [
        'requester_id',
        'recipient_id',
        'status',
        'requester_label_id',
        'recipient_label_id',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function requesterLabel()
    {
        return $this->belongsTo(ConnectionLabel::class, 'requester_label_id');
    }

    public function recipientLabel()
    {
        return $this->belongsTo(ConnectionLabel::class, 'recipient_label_id');
    }

    /** The other party of this connection, from $userId's perspective. */
    public function otherUserId(int $userId): int
    {
        return $this->requester_id === $userId ? $this->recipient_id : $this->requester_id;
    }

    /** Whether $userId is a party to this connection at all. */
    public function involves(int $userId): bool
    {
        return $this->requester_id === $userId || $this->recipient_id === $userId;
    }
}
