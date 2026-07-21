<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $number_label }}</title>
    <style>
        {{-- dompdf's CSS support excludes flexbox/grid -- table/block layout only. --}}
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #292522; }
        .header-table { width: 100%; border-bottom: 3px solid #E7653B; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { font-size: 20px; font-weight: bold; color: #E7653B; }
        .doc-title { font-size: 16px; font-weight: bold; text-align: right; }
        .doc-number { font-size: 11px; text-align: right; color: #555; }
        .draft-banner { background: #FFF8E8; border: 1px solid #F4B942; color: #92400e; padding: 8px 12px;
            text-align: center; font-weight: bold; margin-bottom: 16px; }
        .party-table { width: 100%; margin-bottom: 16px; }
        .party-table td { vertical-align: top; width: 50%; padding-right: 12px; }
        .party-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; margin-bottom: 4px; }
        .party-name { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .party-line { font-size: 10px; color: #444; margin-bottom: 1px; }
        .meta-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .meta-table td { padding: 4px 0; font-size: 10px; }
        .meta-table .label { color: #888; width: 140px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table th { background: #FFF8E8; text-align: left; padding: 8px; font-size: 9px;
            text-transform: uppercase; letter-spacing: 0.5px; color: #555; border-bottom: 1px solid #ddd; }
        .items-table td { padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        .amount-cell { text-align: right; white-space: nowrap; }
        .totals-table { width: 100%; margin-top: 8px; }
        .totals-table td { padding: 3px 8px; font-size: 11px; }
        .totals-table .label { text-align: right; color: #555; }
        .totals-table .value { text-align: right; width: 100px; }
        .totals-table .grand-total td { font-weight: bold; font-size: 13px; border-top: 2px solid #292522; padding-top: 6px; }
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #eee; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;"><span class="brand">uLam</span></td>
            <td style="width: 50%;">
                <div class="doc-title">{{ $is_draft ? 'PAYMENT REQUEST' : 'SALES INVOICE' }}</div>
                <div class="doc-number">{{ $number_label }}</div>
            </td>
        </tr>
    </table>

    @if ($is_draft)
        <div class="draft-banner">DRAFT — NOT AN OFFICIAL RECEIPT / SALES INVOICE</div>
    @endif

    <table class="party-table">
        <tr>
            <td>
                <div class="party-label">From</div>
                <div class="party-name">{{ $issuer['trade_name'] ?: ($issuer['registered_name'] ?: 'uLam') }}</div>
                @if ($issuer['registered_name'] && $issuer['trade_name'])
                    <div class="party-line">{{ $issuer['registered_name'] }}</div>
                @endif
                @if ($issuer['address'])<div class="party-line">{{ $issuer['address'] }}</div>@endif
                @if ($issuer['tin'])<div class="party-line">TIN: {{ $issuer['tin'] }}</div>@endif
                @if ($issuer['contact_email'])<div class="party-line">{{ $issuer['contact_email'] }}</div>@endif
                @if ($issuer['contact_phone'])<div class="party-line">{{ $issuer['contact_phone'] }}</div>@endif
            </td>
            <td>
                <div class="party-label">Billed to</div>
                <div class="party-name">{{ $invoice->buyer_name }}</div>
                @if ($invoice->buyer_contact_name)<div class="party-line">Attn: {{ $invoice->buyer_contact_name }}</div>@endif
                @if ($invoice->buyer_address)<div class="party-line">{{ $invoice->buyer_address }}</div>@endif
                @if ($invoice->buyer_email)<div class="party-line">{{ $invoice->buyer_email }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="label">Date</td>
            <td>{{ $date_label }}</td>
        </tr>
        @if ($invoice->sponsoredAd)
            <tr>
                <td class="label">Reference</td>
                <td>Sponsored placement — {{ $invoice->sponsoredAd->product_name }}</td>
            </tr>
        @endif
        @if (! $is_draft && $issuer['atp_number'])
            <tr>
                <td class="label">BIR Authority to Print</td>
                <td>{{ $issuer['atp_number'] }}{{ $issuer['atp_date'] ? ' — issued ' . $issuer['atp_date'] : '' }}</td>
            </tr>
        @endif
    </table>

    <table class="items-table">
        <tr>
            <th>Description</th>
            <th class="amount-cell">Amount</th>
        </tr>
        <tr>
            <td>{{ $invoice->description }}</td>
            <td class="amount-cell">₱{{ number_format($vat['vat_status'] === 'vat_registered' ? $vat['net_amount'] : $invoice->amount, 2) }}</td>
        </tr>
    </table>

    <table class="totals-table">
        @if ($vat['vat_status'] === 'vat_registered')
            <tr>
                <td class="label">VATable Sales</td>
                <td class="value">₱{{ number_format($vat['net_amount'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">VAT (12%)</td>
                <td class="value">₱{{ number_format($vat['vat_amount'], 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="label">Total</td>
            <td class="value">₱{{ number_format($invoice->amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $is_draft
            ? 'This is a payment request, not a valid tax document. An official invoice will be issued once payment is received.'
            : 'This document was issued by ' . ($issuer['registered_name'] ?: 'uLam') . ($issuer['tin'] ? ' (TIN: ' . $issuer['tin'] . ')' : '') . '.' }}
    </div>
</body>
</html>
