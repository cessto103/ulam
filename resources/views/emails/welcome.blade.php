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
        .tagline { color: #bbf7d0; font-size: 13px; margin-top: 4px; }
        .body { padding: 32px; }
        .greeting { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 12px; }
        p { font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0 0 16px; }
        .features { background: #f9fafb; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .feature { display: flex; align-items: flex-start; margin-bottom: 12px; }
        .feature:last-child { margin-bottom: 0; }
        .feature-icon { font-size: 18px; margin-right: 12px; flex-shrink: 0; }
        .feature-text { font-size: 13px; color: #374151; }
        .feature-text strong { display: block; font-weight: 600; color: #111827; }
        .cta { text-align: center; margin: 24px 0; }
        .btn { display: inline-block; background: #16a34a; color: #fff; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px; text-decoration: none; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; text-align: center; }
        .footer p { font-size: 12px; color: #9ca3af; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">uLam</div>
            <div class="tagline">Kain na. Tipid pa.</div>
        </div>
        <div class="body">
            <div class="greeting">Kumusta, {{ $user->name }}!</div>
            <p>Maligayang pagdating sa <strong>uLam</strong> — ang pinaka-budget-friendly na meal planner para sa mga Pilipino. Masaya kaming kasama ka na!</p>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🤖</div>
                    <div class="feature-text">
                        <strong>AI Meal Planning</strong>
                        May 3 libreng AI meal plan ka bawat buwan. Awtomatiko itong gagawa ng almusal, tanghalian, meryenda, at hapunan na angkop sa iyong budget.
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">💰</div>
                    <div class="feature-text">
                        <strong>Budget Tracker</strong>
                        I-track ang iyong pang-araw-araw na gastos sa pagkain at alamin kung magkano ang natipid mo.
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">🏪</div>
                    <div class="feature-text">
                        <strong>Presyo ng Palengke</strong>
                        Makita ang pinakabagong presyo ng mga sangkap mula sa mga tindahan malapit sa iyo.
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">👥</div>
                    <div class="feature-text">
                        <strong>Komunidad</strong>
                        Mag-share ng mga budget recipes at tips kasama ang iyong mga kapitbahay.
                    </div>
                </div>
            </div>

            <p>Para masimulan, i-setup ang iyong monthly budget at hayaan ang uLam na mag-plan ng pagkain para sa iyong pamilya!</p>

            <div class="cta">
                <a href="{{ config('app.url') }}" class="btn">Simulan na ang pag-tipid</a>
            </div>

            <p style="font-size:13px; color:#9ca3af;">Kung hindi ikaw ang nag-sign up, bale-walain mo lang ang email na ito.</p>
        </div>
        <div class="footer">
            <p>uLam &mdash; Para sa bawat Pilipinong pamilya</p>
            <p style="margin-top:4px;">{{ config('app.url') }}</p>
        </div>
    </div>
</div>
</body>
</html>
