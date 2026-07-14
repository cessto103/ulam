<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalDocumentVersion extends Model
{
    protected $fillable = [
        'legal_document_id',
        'version',
        'changelog',
        'content_md',
        'status',
        'author_id',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function contentHtml(): string
    {
        return Str::markdown($this->content_md, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
