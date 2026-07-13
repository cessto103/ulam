<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'question_tl',
        'answer',
        'answer_tl',
        'category',
        'sort',
        'is_published',
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_published' => 'boolean',
    ];
}
