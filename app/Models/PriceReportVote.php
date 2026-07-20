<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceReportVote extends Model
{
    protected $fillable = ['user_id', 'community_price_report_id', 'vote'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function report()
    {
        return $this->belongsTo(CommunityPriceReport::class, 'community_price_report_id');
    }
}
