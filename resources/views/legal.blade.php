<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — uLam</title>
    <style>
        body { margin: 0; padding: 0; background: #FFF8E8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #292522; }
        .wrapper { max-width: 760px; margin: 0 auto; padding: 24px 20px 64px; }
        .header { background: #E7653B; border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; }
        .logo { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .meta { color: rgba(255,248,232,0.9); font-size: 13px; margin-top: 6px; }
        article { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.08); line-height: 1.7; font-size: 15px; }
        article h1 { font-size: 26px; margin-top: 0; }
        article h2 { font-size: 19px; margin-top: 28px; color: #A63F1F; }
        article a { color: #386641; }
        article code { background: #FFF8E8; padding: 1px 5px; border-radius: 4px; }
        .footer { text-align: center; color: #6F655A; font-size: 12px; margin-top: 24px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="logo">uLam</div>
        <div class="meta">{{ $title }} · Version {{ $version }} · Last updated {{ $publishedAt }}</div>
    </div>
    <article>{!! $html !!}</article>
    <div class="footer">uLam — Para sa bawat Pilipinong pamilya</div>
</div>
</body>
</html>
