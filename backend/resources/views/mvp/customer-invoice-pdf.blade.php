<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #172536; }
        h1 { font-size: 22px; margin: 0 0 8px; } h2 { font-size: 12px; margin: 18px 0 7px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 5px 4px; }
        .parties td { width: 50%; line-height: 1.55; }
        .lines { margin-top: 22px; table-layout: fixed; }
        .lines th, .lines td { border-bottom: 1px solid #cad4de; text-align: right; word-wrap: break-word; }
        .lines th:first-child, .lines td:first-child { text-align: left; width: 38%; }
        .totals { width: 42%; margin: 20px 0 0 auto; }
        .totals td:last-child { text-align: right; font-weight: bold; }
        .notice { color: #536474; font-size: 9px; margin-top: 24px; }
    </style>
</head>
<body>
    <h1>Faktura {{ $invoice['document_number'] }}</h1>
    <table>
        <tr><td>Datum vystavení: {{ $invoice['issued_on'] }}</td><td>Datum zdanitelného plnění: {{ $invoice['taxable_supply_on'] ?: '—' }}</td></tr>
        <tr><td>Datum splatnosti: {{ $invoice['due_on'] }}</td><td>Variabilní symbol: {{ $invoice['variable_symbol'] ?: '—' }}</td></tr>
    </table>
    <table class="parties"><tr>
        @foreach (['Dodavatel' => $invoice['issuer'], 'Odběratel' => $invoice['customer']] as $heading => $party)
            <td><h2>{{ $heading }}</h2><strong>{{ $party['name'] ?? '' }}</strong><br>
                {{ $party['street'] ?? '' }}<br>{{ $party['postal_code'] ?? '' }} {{ $party['city'] ?? '' }}<br>
                {{ $party['country_code'] ?? '' }}<br>IČO: {{ $party['registration_number'] ?: '—' }}<br>
                DIČ: {{ $party['vat_number'] ?: '—' }}</td>
        @endforeach
    </tr></table>
    <p>Fakturované období: {{ $invoice['period_from'] }} – {{ $invoice['period_until'] }}</p>
    <table class="lines"><thead><tr><th>Položka</th><th>Množství</th><th>Základ</th><th>DPH</th><th>Celkem</th></tr></thead><tbody>
        @foreach ($invoice['lines'] as $line)
            <tr><td>{{ $line['description'] }}</td><td>{{ $line['quantity'] }}</td>
                <td>{{ $line['net_amount'] }} {{ $invoice['currency'] }}</td>
                <td>{{ $line['vat_amount'] }} {{ $invoice['currency'] }}</td>
                <td>{{ $line['gross_amount'] }} {{ $invoice['currency'] }}</td></tr>
        @endforeach
    </tbody></table>
    <table class="totals">
        <tr><td>Základ</td><td>{{ $invoice['net_amount'] }} {{ $invoice['currency'] }}</td></tr>
        <tr><td>DPH</td><td>{{ $invoice['vat_amount'] }} {{ $invoice['currency'] }}</td></tr>
        <tr><td>K úhradě</td><td>{{ $invoice['gross_amount'] }} {{ $invoice['currency'] }}</td></tr>
    </table>
    @if ($invoice['payment_iban'])
        <p>Účet pro platbu (IBAN): <strong>{{ $invoice['payment_iban'] }}</strong><br>
            Majitel účtu: {{ $invoice['payment_account_holder'] }}</p>
    @else
        <p class="notice">Platební účet není v evidenci dokladu uložen. Pokyny k platbě sdělte odběrateli samostatně.</p>
    @endif
</body>
</html>
