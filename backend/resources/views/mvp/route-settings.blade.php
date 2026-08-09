<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nastavení tras | TMS</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: #111827; font-family: Arial, sans-serif; }
        .page { max-width: 1180px; margin: 0 auto; padding: 32px 24px 60px; }
        .back { display: inline-block; margin-bottom: 18px; color: #374151; font-weight: 700; text-decoration: none; }
        h1 { margin-bottom: 8px; }
        .lead { max-width: 900px; margin-top: 0; color: #6b7280; line-height: 1.55; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap: 16px; margin-top: 28px; }
        .card { display: block; padding: 20px; border: 1px solid #d1d5db; border-radius: 12px; background: #ffffff; color: #111827; text-decoration: none; }
        .card h2 { margin: 0 0 8px; font-size: 18px; }
        .card p { margin: 0; color: #6b7280; line-height: 1.5; }
        .panel { margin-top: 24px; padding: 20px; border: 1px solid #d1d5db; border-radius: 12px; background: #ffffff; }
        .panel h2 { margin-top: 0; }
        .table-shell { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .table-shell th, .table-shell td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .table-shell th { background: #f9fafb; font-size: 13px; }
        .empty { color: #6b7280; text-align: center !important; padding: 26px !important; }
        .note { margin-top: 22px; padding: 15px 16px; border-left: 4px solid #9ca3af; background: #f3f4f6; line-height: 1.55; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #f3f4f6; color: #4b5563; font-size: 12px; font-weight: 700; }
    </style>
</head>
<body>
<div class="page">
    <a class="back" href="/settings">← Nastavení</a>
    <h1>Nastavení tras</h1>
    <p class="lead">Výchozí provozní pravidla. Konkrétní trasa bude moci mít vlastní nastavení podle skutečných podmínek.</p>
    <div class="grid">
        <div class="card"><h2>Obtížnost trasy</h2><p>Standardní, zvýšená nebo vysoká. Nezávisle na charakteru městská / venkovská / smíšená.</p></div>
        <div class="card"><h2>Tolerance km</h2><p>Výchozí pravidlo pro odchylku nájezdu. Route-specific nastavení bude mít přednost.</p></div>
        <div class="card"><h2>Přesměrované zásilky</h2><p>Výchozí tolerance s možností individuálního nastavení konkrétní trasy.</p></div>
        <div class="card"><h2>Nedoručené zásilky</h2><p>Výchozí provozní tolerance pro upozornění a kontrolu.</p></div>
    </div>
    <div class="note">
        Po schválení dispečerem již předchozí business warningy neblokují navazující workflow. Původní hodnoty zůstávají auditně zachované.
    </div>
</div>
</body>
</html>
