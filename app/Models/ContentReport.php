<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
        'reason',
        'details',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
