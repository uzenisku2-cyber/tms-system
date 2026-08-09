<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Číselníky | TMS</title>
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
    <h1>Číselníky</h1>
    <p class="lead">Sdílené provozní definice používané v jednotlivých modulech TMS.</p>
    <div class="grid">
        <a class="card" href="/settings/catalogs/routes" data-testid="catalog-routes">
            <h2>Trasy</h2>
            <p>Aktuální označení tras, jejich historická kontinuita, oblast a stav.</p>
        </a>
        <a class="card" href="/settings/catalogs/route-characters" data-testid="catalog-route-characters">
            <h2>Charakter tras</h2>
            <p>Městská, venkovská, smíšená a další popisy provozního prostředí.</p>
        </a>
        <a class="card" href="/settings/catalogs/operational-reasons" data-testid="catalog-operational-reasons">
            <h2>Provozní důvody</h2>
            <p>Odvod hotovosti, objížďka, pošta, banka a další standardní důvody odchylek.</p>
        </a>
    </div>
</div>
</body>
</html>
