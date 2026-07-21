<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    // Matches the DB column default -- without this, a freshly-instantiated
    // Invoice (e.g. right after create(), before any fresh() re-fetch) reads
    // status as '' in memory, not 'draft', until the next DB round-trip.
    // Confirmed to matter in practice: caused a null->format() crash in
    // InvoiceService when status was read off a non-refreshed instance.
    protected $attributes = [
        'status' => 'draft',
    ];

    protected $fillable = [
        'sponsored_ad_id',
        'created_by',
        'buyer_name',
        'buyer_contact_name',
        'buyer_email',
        'buyer_address',
        'description',
        'amount',
        'notes',
    ];

    // Deliberately NOT fillable: invoice_number, status, vat_status,
    // net_amount, vat_amount, issuer_snapshot, pdf_path, issued_at,
    // voided_at, voided_by, void_reason -- every one of these is written
    // only by InvoiceService, never from a draft create/update payload.

    protected $casts = [
        'amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'issuer_snapshot' => 'array',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function sponsoredAd()
    {
        return $this->belongsTo(SponsoredAd::class);
    }

    // Named *ByUser, not createdBy/voidedBy -- this model also has raw
    // created_by/voided_by integer FK columns, and Eloquent snake-cases a
    // relation method name for its array/JSON key (createdBy -> created_by),
    // which would silently collide with and overwrite the raw column's own
    // value once eager-loaded. Confirmed live: without this, eager-loading
    // voidedBy replaced the plain int voided_by with a nested user object.
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * VAT-inclusive-pricing convention: `amount` is assumed to already
     * include VAT, so net = amount / 1.12 and vat = amount - net. NOT YET
     * CONFIRMED against a real BIR-registered convention -- isolated here
     * as the one place to change if amount should instead be VAT-exclusive.
     */
    public static function computeVat(string|float $amount, ?string $vatStatus): array
    {
        if ($vatStatus !== 'vat_registered') {
            return ['vat_status' => $vatStatus, 'net_amount' => null, 'vat_amount' => null];
        }

        $net = round(((float) $amount) / 1.12, 2);

        return [
            'vat_status' => $vatStatus,
            'net_amount' => $net,
            'vat_amount' => round(((float) $amount) - $net, 2),
        ];
    }
}
