<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->status === 'draft' ? 'Payment Request' : 'Invoice' }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FFF8E8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper { max-width: 480px; margin: 32px auto; }
        .card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: #E7653B; padding: 32px; text-align: center; }
        .logo { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .body { padding: 32px; text-align: center; }
        .greeting { font-size: 16px; font-weight: 700; color: #292522; margin-bottom: 8px; }
        p { font-size: 14px; color: #6F655A; line-height: 1.6; margin: 0 0 16px; }
        .amount { font-size: 28px; font-weight: 800; color: #386641; background: #FFF8E8; border-radius: 12px; padding: 16px 12px; margin: 8px 0 20px; }
        .footer { padding: 20px 32px; border-top: 1px solid #F9EDD3; text-align: center; }
        .footer p { font-size: 12px; color: #B0A18C; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">uLam</div>
        </div>
        <div class="body">
            <div class="greeting">Hi {{ $invoice->buyer_contact_name ?? $invoice->buyer_name }},</div>
            @if ($invoice->status === 'draft')
                <p>Please find attached a payment request for {{ $invoice->description }}. This is not yet an official tax document — the final invoice will be issued once payment is received.</p>
            @else
                <p>Please find attached your official invoice {{ $invoice->invoice_number }} for {{ $invoice->description }}.</p>
            @endif
            <div class="amount">₱{{ number_format((float) $invoice->amount, 2) }}</div>
            <p style="font-size:13px; color:#B0A18C;">Questions about this invoice? Just reply to this email.</p>
        </div>
        <div class="footer">
            <p>uLam &mdash; Para sa bawat Pilipinong pamilya</p>
        </div>
    </div>
</div>
</body>
</html>
