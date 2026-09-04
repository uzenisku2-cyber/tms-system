<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use DateTimeImmutable;
use RuntimeException;

final class FuelTransactionCsvExportService
{
    private const HEADERS = [
        "Datum a \u{010D}as", 'Poskytovatel', 'Stanice', 'Produkt', "Mno\u{017E}stv\u{00ED}", 'Jednotka',
        'Celkem', "M\u{011B}na", 'Karta', "Dr\u{017E}itel karty", "Skute\u{010D}n\u{00FD} \u{0159}idi\u{010D}",
        'Vozidlo', "Provozn\u{00ED} shoda", 'Detail shody',
    ];

    private const STATUS_LABELS = [
        'pending' => 'Nevyhodnoceno',
        'matched' => 'Shoda nalezena',
        'review_required' => "Vy\u{017E}aduje kontrolu",
        'resolved' => "Vy\u{0159}e\u{0161}eno",
    ];

    private const RESULT_LABELS = [
        'import_requires_review' => "Import vy\u{017E}aduje kontrolu",
        'missing_effective_driver' => "Chyb\u{00ED} skute\u{010D}n\u{00FD} \u{0159}idi\u{010D}",
        'missing_driver_organization_assignment' => "Chyb\u{00ED} organiza\u{010D}n\u{00ED} za\u{0159}azen\u{00ED}",
        'no_operational_activity' => "Nenalezena provozn\u{00ED} aktivita",
        'driver_day_matched' => "Shoda \u{0159}idi\u{010D}e a dne",
        'vehicle_matched' => "Shoda vozidla a z\u{00E1}znamu",
        'vehicle_mismatch' => 'Neshoda vozidla',
        'vehicle_unconfirmed' => 'Vozidlo nepotvrzeno',
        'manual_confirm_driver_day' => "Ru\u{010D}n\u{011B} potvrzen den \u{0159}idi\u{010D}e",
        'manual_select_daily_report' => "Ru\u{010D}n\u{011B} vybr\u{00E1}n denn\u{00ED} z\u{00E1}znam",
        'manual_accept_without_operational_activity' => "P\u{0159}ijato bez provozn\u{00ED} aktivity",
        'manual_return_to_review' => "Vr\u{00E1}ceno ke kontrole",
    ];

    public function filename(): string
    {
        return 'prehled-tankovani-'.now()->format('Ymd-His').'.csv';
    }

    /**
     * @param  iterable<array<string, mixed>>  $items
     * @param  resource  $output
     */
    public function write(iterable $items, mixed $output): int
    {
        if (! is_resource($output)) {
            throw new RuntimeException('The CSV output must be a writable resource.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        $this->put($output, self::HEADERS);
        $rowCount = 0;

        foreach ($items as $item) {
            $reconciliation = is_array($item['reconciliation'] ?? null) ? $item['reconciliation'] : [];
            $this->put($output, [
                $this->sourceDate($item['occurred_at'] ?? null),
                (string) ($item['provider'] ?? ''),
                (string) ($item['station_name'] ?? ''),
                (string) ($item['product_name'] ?? ''),
                $this->decimal($item['quantity'] ?? null, 3),
                (string) ($item['unit_of_measure'] ?? ''),
                $this->decimal($item['gross_amount'] ?? null, 2),
                (string) ($item['currency'] ?? ''),
                (string) ($item['masked_card'] ?? ''),
                $this->driverName($item['imported_driver'] ?? null),
                $this->driverName($item['effective_driver'] ?? null),
                (string) ($item['vehicle_registration'] ?? ''),
                self::STATUS_LABELS[(string) ($reconciliation['status'] ?? 'pending')] ?? (string) ($reconciliation['status'] ?? ''),
                self::RESULT_LABELS[(string) ($reconciliation['result_code'] ?? '')] ?? (string) ($reconciliation['result_code'] ?? ''),
            ]);
            $rowCount++;
        }

        return $rowCount;
    }

    /**
     * @param  resource  $output
     * @param  array<int, string>  $row
     */
    private function put(mixed $output, array $row): void
    {
        if (fputcsv($output, $row, ';', '"', '') === false) {
            throw new RuntimeException('Unable to write a CSV row.');
        }
    }

    private function sourceDate(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date === false ? $value : $date->format('d.m.Y H:i:s');
    }

    private function decimal(mixed $value, int $scale): string
    {
        if (! is_numeric($value)) {
            return '';
        }
        $formatted = number_format((float) $value, $scale, ',', '');

        return $scale === 2 ? $formatted : rtrim(rtrim($formatted, '0'), ',');
    }

    private function driverName(mixed $driver): string
    {
        return is_array($driver) ? (string) ($driver['name'] ?? '') : '';
    }
}
