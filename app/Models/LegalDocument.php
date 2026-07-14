<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = ['slug', 'title'];

    public function versions()
    {
        return $this->hasMany(LegalDocumentVersion::class);
    }

    public function publishedVersion()
    {
        return $this->hasOne(LegalDocumentVersion::class)->where('status', 'published');
    }
}
