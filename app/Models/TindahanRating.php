<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindahanRating extends Model
{
    protected $fillable = ['user_id', 'tindahan_id', 'rating'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }
}
