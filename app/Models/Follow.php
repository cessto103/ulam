<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = [
        'follower_id',
        'followed_id',
    ];

    // Relation names are wire-compatibility critical: the /connections/following
    // and /connections/followers endpoints serialize eager-loaded relations
    // straight into JSON, and mobile builds in the field read `item.recipient`
    // and `item.requester` — the names the old Connection model used. Renaming
    // these would silently blank every deployed client's lists.
    public function requester()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'followed_id');
    }
}
