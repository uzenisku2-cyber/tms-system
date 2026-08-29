<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use App\Modules\DailyReports\Services\DepotWorkbookReader;
use App\Modules\Fuel\Services\FuelTransactionImportService;
use Tests\Support\DepotWorkbookFactory;
use Tests\TestCase;

final class FuelProviderTransactionNormalizerTest extends TestCase
{
    public function test_real_orlen_shape_normalizes_czech_csv_values_without_numeric_card_coercion(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'orlen-');
        self::assertIsString($path);
        $headers = ['Číslo účtenky', 'Datum a čas prodeje', 'Číslo karty', 'RZ', 'Jméno řidiče', 'Zákaznická položka', 'Label', 'Typ transakce', 'Množství', 'Jednotková cena', 'Jednotková cena po slevě', 'Celková cena', 'Celková cena po slevě', 'Sleva', 'Sazba DPH', 'DPH', 'Celková cena (bez DPH)', 'Měna', 'Stav tachometru', 'Čerpací stanice', 'Adresa čerpací stanice', 'Produkt', 'OBU', 'VS pohledávky', 'Faktura číslo', 'Číslo střediska', 'Středisko'];
        $values = ['R-1', '31.7.2026 07:54:51', "7082749167400600031\u{00A0}", '', '', '', '', 'Platba', '40,41', '42,90', '42,10', '1733,60', '1701,27', '-32,33', '21,00', '300,87', '1432,73', 'CZK', '', '131 - PLZEŇ, BORY', 'Plzeň', '022 Efecta 95', '', '1', '2', '674006', 'Carrier'];

        try {
            $handle = fopen($path, 'wb');
            self::assertNotFalse($handle);
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';', '"', '\\');
            fputcsv($handle, $values, ';', '"', '\\');
            fclose($handle);
            $preview = (new FuelTransactionImportService(new DepotWorkbookReader))->preview('ORLEN', $path);
            self::assertCount(1, $preview['rows']);
            $row = $preview['rows'][0]['normalized'];
            self::assertSame('7082749167400600031', $row['provider_card_identifier']);
            self::assertSame('2026-07-31 07:54:51', $row['occurred_at']);
            self::assertSame('40.41', $row['quantity']);
            self::assertSame('1701.27', $row['gross_amount']);
            self::assertSame('1406.008264', $row['net_amount']);
            self::assertSame('295.261736', $row['tax_amount']);
            self::assertSame('-32.33', $row['discount_amount']);
            self::assertSame(
                0,
                bccomp(
                    $row['gross_amount'],
                    bcadd($row['net_amount'], $row['tax_amount'], 6),
                    6,
                ),
            );
            self::assertSame('R-1', $row['provider_transaction_identifier']);
        } finally {
            @unlink($path);
        }
    }

    public function test_real_mol_shape_normalizes_excel_serial_and_unnamed_litre_column(): void
    {
        $headers = ['Akceptor (orig.)', 'Akceptor', 'Typ smlouvy', 'ID zákazníka', 'Jméno zákazníka', 'Datum transakce', 'Číslo karty', 'Registrační značka', 'Stav tachometru', 'Stav KM', 'Nákladové středisko', 'Kód produktu', 'Produkt', 'Skupina produktů', 'Čerpací stanice', 'Název čerpací stanice', 'Opravný doklad č.', 'Č. faktury', 'Typ faktury', 'Množství', null, 'Jednotková cena', 'Měna', 'Znak', 'Částka', 'Cenotvorba', 'Ceníková cena', 'Sleva/Jednotka', 'Použitá jednotková cena', 'Fakturovaná částka', 'DPH %', 'Číslo stvrzenky'];
        $values = ['MSCZ', 'MSCZ', 'FLEET', '1', 'Carrier', 45809.84, '7080056000871905   ', '6', '0', 0, '', '085001', 'EVO 95', '000', '94116', 'PLZEN', '', ' 2610650301', 'INVOICE01', 24.12, 'L', 34.5, 'CZK', '+', 832.14, 'Totemová cena', 34.29, 0.605, 33.895, 817.5474, 21, '298262'];
        $path = DepotWorkbookFactory::create([$headers, $values], [], ' Podrobný report o transakcích');

        try {
            $preview = (new FuelTransactionImportService(new DepotWorkbookReader))->preview('MOL', $path);
            self::assertCount(1, $preview['rows']);
            $row = $preview['rows'][0]['normalized'];
            self::assertSame('7080056000871905', $row['provider_card_identifier']);
            self::assertSame('L', $row['unit_of_measure']);
            self::assertSame('817.5474', $row['gross_amount']);
            self::assertSame('298262', $row['provider_transaction_identifier']);
        } finally {
            @unlink($path);
        }
    }
}
