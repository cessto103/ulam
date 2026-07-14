<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLegalAcceptance extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'legal_document_id',
        'legal_document_version_id',
        'accepted_at',
        'ip',
        'device',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function version()
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }
}
