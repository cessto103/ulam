<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindahanComment extends Model
{
    protected $fillable = ['user_id', 'tindahan_id', 'parent_id', 'body'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tindahan()
    {
        return $this->belongsTo(Tindahan::class);
    }

    public function replies()
    {
        return $this->hasMany(TindahanComment::class, 'parent_id');
    }
}
