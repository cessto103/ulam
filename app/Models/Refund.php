<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Refund extends Model
{
    protected $fillable = ['payment_id', 'requested_by', 'provider', 'provider_refund_id', 'amount', 'currency', 'reason', 'status', 'metadata', 'processed_at'];
    protected $casts = ['metadata' => 'array', 'processed_at' => 'datetime'];
    public function payment() { return $this->belongsTo(Payment::class); }
}
