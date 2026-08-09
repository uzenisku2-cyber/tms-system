<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Charakter tras | TMS</title>
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
    <a class="back" href="/settings/catalogs">← Číselníky</a>
    <h1>Charakter tras</h1>
    <p class="lead">Charakter popisuje prostředí trasy. Je záměrně oddělený od obtížnosti.</p>
    <div class="panel">
        <h2>Základní hodnoty</h2>
        <table class="table-shell">
            <thead>
                <tr>
                    <th>Kód</th>
                    <th>Název</th>
                    <th>Popis</th>
                    <th>Stav</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>URBAN</td><td>Městská</td><td>Vyšší hustota zastávek a městský provoz.</td><td><span class="badge">Návrh</span></td></tr>
                <tr><td>RURAL</td><td>Venkovská</td><td>Delší přejezdy a nižší hustota zastávek.</td><td><span class="badge">Návrh</span></td></tr>
                <tr><td>MIXED</td><td>Smíšená</td><td>Kombinace městských a venkovských úseků.</td><td><span class="badge">Návrh</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="note">
        Městská trasa nemusí být automaticky obtížná a venkovská nemusí být automaticky snadná. Obtížnost bude nastavována samostatně.
    </div>
</div>
</body>
</html>
