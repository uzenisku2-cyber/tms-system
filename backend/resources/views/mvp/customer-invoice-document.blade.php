<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faktura {{ $invoice['document_number'] }}</title>
    <style>
        @page { size: A4; margin: 17mm; }
        body { font: 14px/1.45 Arial, sans-serif; color: #172536; max-width: 920px; margin: 24px auto; }
        h1 { font-size: 29px; margin: 0 0 8px; } h2 { font-size: 16px; }
        .top, .parties, .totals { display: flex; justify-content: space-between; gap: 28px; }
        .parties > div { flex: 1; } .meta { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin: 26px 0; }
        th, td { border-bottom: 1px solid #cad4de; padding: 9px 6px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        .totals { justify-content: flex-end; } .totals dl { min-width: 240px; }
        dt { float: left; } dd { text-align: right; margin: 0 0 8px; font-weight: bold; }
        .print { margin-bottom: 22px; } .notice { font-size: 12px; color: #536474; }
        @media print { body { margin: 0; max-width: none; } .print { display: none; } }
    </style>
</head>
<body>
    <button class="print" type="button" onclick="window.print()">Tisk / uložit jako PDF</button>
    <header class="top">
        <div><h1>Faktura</h1><strong>{{ $invoice['document_number'] }}</strong></div>
        <div class="meta">
            <div>Datum vystavení: {{ $invoice['issued_on'] }}</div>
            <div>Datum zdanitelného plnění: {{ $invoice['taxable_supply_on'] ?: '—' }}</div>
            <div>Datum splatnosti: {{ $invoice['due_on'] }}</div>
            <div>Variabilní symbol: {{ $invoice['variable_symbol'] ?: '—' }}</div>
        </div>
    </header>
    <section class="parties">
        @foreach (['Dodavatel' => $invoice['issuer'], 'Odběratel' => $invoice['customer']] as $heading => $party)
            <div><h2>{{ $heading }}</h2>
                <strong>{{ $party['name'] ?? '' }}</strong><br>
                {{ $party['street'] ?? '' }}<br>
                {{ $party['postal_code'] ?? '' }} {{ $party['city'] ?? '' }}<br>
                {{ $party['country_code'] ?? '' }}<br>
                IČO: {{ $party['registration_number'] ?: '—' }}<br>
                DIČ: {{ $party['vat_number'] ?: '—' }}
            </div>
        @endforeach
    </section>
    <p>Fakturované období: {{ $invoice['period_from'] }} – {{ $invoice['period_until'] }}</p>
    <table>
        <thead><tr><th>Položka</th><th>Množství</th><th>Základ</th><th>DPH</th><th>Celkem</th></tr></thead>
        <tbody>
            @foreach ($invoice['lines'] as $line)
                <tr>
                    <td>{{ $line['description'] }}</td>
                    <td>{{ $line['quantity'] }}</td>
                    <td>{{ $line['net_amount'] }} {{ $invoice['currency'] }}</td>
                    <td>{{ $line['vat_amount'] }} {{ $invoice['currency'] }}</td>
                    <td>{{ $line['gross_amount'] }} {{ $invoice['currency'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals"><dl>
        <dt>Základ</dt><dd>{{ $invoice['net_amount'] }} {{ $invoice['currency'] }}</dd>
        <dt>DPH</dt><dd>{{ $invoice['vat_amount'] }} {{ $invoice['currency'] }}</dd>
        <dt>K úhradě</dt><dd>{{ $invoice['gross_amount'] }} {{ $invoice['currency'] }}</dd>
    </dl></div>
    @if ($invoice['payment_iban'])
        <p><strong>Účet pro platbu (IBAN):</strong> {{ $invoice['payment_iban'] }}<br>
            <strong>Majitel účtu:</strong> {{ $invoice['payment_account_holder'] }}</p>
    @else
        <p class="notice">Platební účet není v evidenci dokladu uložen. Pokyny k platbě sdělte odběrateli samostatně.</p>
    @endif
</body>
</html>
