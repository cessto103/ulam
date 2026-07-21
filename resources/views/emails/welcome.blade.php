<!DOCTYPE html>
<html lang="fil">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maligayang pagdating sa uLam</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper { max-width: 520px; margin: 32px auto; }
        .card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); padding: 36px 32px; text-align: center; }
        .logo { font-size: 28px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .logo img { max-height: 40px; display: inline-block; }
        .tagline { color: #bbf7d0; font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; }
        .greeting { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 12px; }
        p { font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0 0 16px; }
        .intro img { max-width: 100%; border-radius: 12px; margin: 8px 0 16px; }
        .intro ul { padding-inline-start: 20px; margin: 0 0 16px; }
        .intro li { font-size: 14px; color: #374151; line-height: 1.7; }
        .intro li strong { color: #111827; }
        .intro :last-child { margin-bottom: 0; }
        .cta { text-align: center; margin: 24px 0; }
        .btn { display: inline-block; background: #16a34a; color: #fff; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px; text-decoration: none; }
        .note { font-size: 13px; color: #9ca3af; }
        .note :last-child { margin-bottom: 0; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="uLam">
                @else
                    uLam
                @endif
            </div>
            <div class="tagline">Kain na. Tipid pa.</div>
        </div>
        <div class="body">
            <div class="greeting">Kumusta, {{ $user->name }}!</div>
            <div class="intro">{!! $introHtml !!}</div>

            @if($ctaLabel)
                <div class="cta">
                    <a href="{{ config('app.url') }}" class="btn">{{ $ctaLabel }}</a>
                </div>
            @endif

            @if($noteHtml)
                <div class="note">{!! $noteHtml !!}</div>
            @endif
        </div>
        <div class="footer">
            <p>uLam &mdash; Para sa bawat Pilipinong pamilya</p>
            <p style="margin-top:4px;">{{ config('app.url') }}</p>
        </div>
    </div>
</div>
</body>
</html>
