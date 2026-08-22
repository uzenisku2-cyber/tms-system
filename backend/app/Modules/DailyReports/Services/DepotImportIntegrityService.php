<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportRow;
use DateTimeInterface;
use JsonException;
use LogicException;

final class DepotImportIntegrityService
{
    /** @var list<string> */
    private const PROTECTED_ROW_FIELDS = [
        'source_row',
        'status',
        'service_date',
        'route_number',
        'route_number_normalized',
        'carrier_name',
        'source_driver_name',
        'source_driver_key',
        'departure_time',
        'arrival_time',
        'actual_km',
        'planned_km',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'customer_rejected_parcels',
        'reported_not_delivered_parcels',
        'computed_not_delivered_parcels',
        'surcharge_amount',
        'operational_notes',
        'errors',
        'warnings',
    ];

    /**
     * @param  DepotImportRow|array<string, mixed>  $row
     */
    public function protectedRowHash(
        DepotImportRow|array $row,
    ): string {
        return $this->hash(
            $this->protectedRowPayload($row),
        );
    }

    /**
     * @param  iterable<int, DepotImportRow>  $rows
     * @return array<string, int|string>
     */
    public function totals(iterable $rows): array
    {
        $totals = [
            'matched_rows' => 0,
            'ready_rows' => 0,
            'invalid_rows' => 0,
            'no_run_rows' => 0,
            'loaded_parcels' => 0,
            'delivered_parcels' => 0,
            'redirected_parcels' => 0,
            'customer_rejected_parcels' => 0,
            'computed_not_delivered_parcels' => 0,
            'actual_km_hundredths' => 0,
            'planned_km_hundredths' => 0,
            'surcharge_hundredths' => 0,
        ];

        foreach ($rows as $row) {
            $totals['matched_rows']++;
            $status = (string) $row->getAttribute('status');

            if ($status === DepotImportRow::STATUS_READY) {
                $totals['ready_rows']++;
            } elseif ($status === DepotImportRow::STATUS_NO_RUN) {
                $totals['no_run_rows']++;

                continue;
            } else {
                $totals['invalid_rows']++;

                continue;
            }

            foreach ([
                'loaded_parcels',
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
                'computed_not_delivered_parcels',
            ] as $field) {
                $totals[$field] += (int) (
                    $row->getAttribute($field) ?? 0
                );
            }

            $totals['actual_km_hundredths'] +=
                $this->decimalToHundredths(
                    $row->getAttribute('actual_km'),
                );
            $totals['planned_km_hundredths'] +=
                $this->decimalToHundredths(
                    $row->getAttribute('planned_km'),
                );
            $totals['surcharge_hundredths'] +=
                $this->decimalToHundredths(
                    $row->getAttribute('surcharge_amount'),
                );
        }

        $totals['actual_km'] = $this->decimalString(
            $totals['actual_km_hundredths'],
        );
        $totals['planned_km'] = $this->decimalString(
            $totals['planned_km_hundredths'],
        );
        $totals['surcharge_amount'] = $this->decimalString(
            $totals['surcharge_hundredths'],
        );

        unset(
            $totals['actual_km_hundredths'],
            $totals['planned_km_hundredths'],
            $totals['surcharge_hundredths'],
        );

        return $totals;
    }

    /** @param  array<string, mixed>  $totals */
    public function totalsHash(array $totals): string
    {
        return $this->hash($totals);
    }

    /**
     * @param  iterable<int, DepotImportRow>  $rows
     */
    public function assertBatchIntegrity(
        DepotImportBatch $batch,
        iterable $rows,
    ): void {
        $materializedRows = [];

        foreach ($rows as $row) {
            $expectedRowHash = (string) $row->getAttribute(
                'protected_values_sha256',
            );
            $actualRowHash = $this->protectedRowHash($row);

            if (! hash_equals($expectedRowHash, $actualRowHash)) {
                throw new LogicException(
                    sprintf(
                        'Protected depot-import values changed for source row %d.',
                        (int) $row->getAttribute('source_row'),
                    ),
                );
            }

            $materializedRows[] = $row;
        }

        $totals = $this->totals($materializedRows);
        $actualTotalsHash = $this->totalsHash($totals);
        $storedTotals = $batch->getAttribute('source_totals');

        if (! is_array($storedTotals)) {
            throw new LogicException(
                'Protected depot-import totals are unavailable.',
            );
        }

        $storedTotalsHash = $this->totalsHash($storedTotals);
        $expectedTotalsHash = (string) $batch->getAttribute(
            'protected_totals_sha256',
        );

        if (
            ! hash_equals($expectedTotalsHash, $storedTotalsHash)
            || ! hash_equals($expectedTotalsHash, $actualTotalsHash)
        ) {
            throw new LogicException(
                'Protected depot-import control totals changed.',
            );
        }
    }

    /**
     * @param  DepotImportRow|array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function protectedRowPayload(
        DepotImportRow|array $row,
    ): array {
        $payload = [];

        foreach (self::PROTECTED_ROW_FIELDS as $field) {
            $value = $row instanceof DepotImportRow
                ? $row->getAttribute($field)
                : ($row[$field] ?? null);

            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if (
                in_array(
                    $field,
                    ['actual_km', 'planned_km', 'surcharge_amount'],
                    true,
                )
                && $value !== null
            ) {
                $value = number_format(
                    (float) $value,
                    2,
                    '.',
                    '',
                );
            }

            if (
                in_array(
                    $field,
                    ['departure_time', 'arrival_time'],
                    true,
                )
                && is_string($value)
                && preg_match('/^\d{2}:\d{2}/', $value) === 1
            ) {
                $value = substr($value, 0, 5);
            }

            $payload[$field] = $value;
        }

        return $payload;
    }

    private function decimalToHundredths(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(
            (float) $value * 100,
            0,
            PHP_ROUND_HALF_UP,
        );
    }

    private function decimalString(int $hundredths): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($hundredths, 100),
            abs($hundredths % 100),
        );
    }

    private function hash(mixed $value): string
    {
        try {
            $encoded = json_encode(
                $this->canonicalize($value),
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new LogicException(
                'Depot-import integrity payload cannot be encoded.',
                0,
                $exception,
            );
        }

        return strtoupper(hash('sha256', $encoded));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->canonicalize($item),
                $value,
            );
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
