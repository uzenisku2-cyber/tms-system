<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Exceptions\DepotWorkbookException;
use Normalizer;

final class DepotImportHeaderDetector
{
    /** @var list<string> */
    private const REQUIRED_FIELDS = [
        'service_date',
        'route_number',
        'carrier_name',
        'source_driver_name',
        'loaded_parcels',
        'delivered_parcels',
    ];

    /** @var array<string, list<string>> */
    private const EXACT_ALIASES = [
        'year' => [
            'rok',
            'year',
        ],
        'month' => [
            'mesic',
            'month',
        ],
        'service_date' => [
            'datum',
            'datum trasy',
            'den',
            'service date',
        ],
        'route_number' => [
            'trasa',
            'trasa c',
            'cislo trasy',
            'trasa cislo',
            'route',
            'route number',
        ],
        'carrier_name' => [
            'dopravce',
            'nazev dopravce',
            'carrier',
            'carrier name',
        ],
        'source_driver_name' => [
            'jmeno ridice',
            'ridic',
            'ridic jmeno',
            'driver',
            'driver name',
        ],
        'operational_notes' => [
            'poznamka',
            'poznamky',
            'note',
            'notes',
        ],
        'departure_time' => [
            'cas odjezdu',
            'odjezd',
            'departure time',
        ],
        'arrival_time' => [
            'cas prijezdu',
            'prijezd',
            'arrival time',
        ],
        'actual_km' => [
            'trasa km namerena position',
            'trasa km namerena',
            'namerene km',
            'skutecne km',
            'actual km',
        ],
        'planned_km' => [
            'trasa km planovana position',
            'trasa km planovana',
            'planovane km',
            'plan km',
            'planned km',
        ],
        'loaded_parcels' => [
            'nalozeno',
            'nalozeno ks',
            'nalozene zasilky',
            'loaded',
            'loaded parcels',
        ],
        'delivered_parcels' => [
            'doruceno na adresu',
            'doruceno na adresu ks',
            'dorucene zasilky',
            'delivered to address',
        ],
        'redirected_parcels' => [
            'doruceno na vm',
            'doruceno na vm ks',
            'doruceno na vydejni misto',
            'presmerovane zasilky',
            'redirected parcels',
        ],
        'customer_rejected_parcels' => [
            'odmitnute',
            'odmitnute ks',
            'odmitnuto zakaznikem',
            'zasilky odmitnute zakaznikem',
            'customer rejected parcels',
        ],
        'surcharge_amount' => [
            'priplatky',
            'priplatky kc',
            'priplatek',
            'priplatek kc',
            'surcharge',
        ],
    ];

    /** @var array<string, list<string>> */
    private const PREFIX_ALIASES = [
        'operational_notes' => [
            'poznamka ',
            'poznamky ',
        ],
        'loaded_parcels' => [
            'nalozeno ',
            'nalozene zasilky ',
        ],
        'delivered_parcels' => [
            'doruceno na adresu ',
            'dorucene zasilky ',
        ],
        'redirected_parcels' => [
            'doruceno na vm ',
            'doruceno na vydejni misto ',
            'presmerovane zasilky ',
        ],
        'customer_rejected_parcels' => [
            'odmitnute ',
            'odmitnuto zakaznikem ',
        ],
    ];

    /**
     * @param  list<array{
     *     name:string,
     *     path:string,
     *     rows:list<array{
     *         row:int,
     *         cells:array<int, array{
     *             reference:string,
     *             value:?string,
     *             formula:bool
     *         }>
     *     }>
     * }>  $sheets
     * @return array{
     *     sheet_index:int,
     *     sheet_name:string,
     *     sheet_path:string,
     *     header_start_row:int,
     *     header_end_row:int,
     *     data_start_row:int,
     *     columns:array<string, array{
     *         column:int,
     *         letter:string,
     *         header:string
     *     }>,
     *     schema_fingerprint:string
     * }
     */
    public function detect(array $sheets): array
    {
        $candidates = [];

        foreach ($sheets as $sheetIndex => $sheet) {
            $rowsByNumber = [];

            foreach ($sheet['rows'] as $row) {
                if ($row['row'] <= 25) {
                    $rowsByNumber[$row['row']] = $row['cells'];
                }
            }

            for ($start = 1; $start <= 25; $start++) {
                for ($depth = 1; $depth <= 3; $depth++) {
                    $candidate = $this->candidate(
                        $rowsByNumber,
                        $start,
                        $depth,
                    );

                    if ($candidate === null) {
                        continue;
                    }

                    $candidate['sheet_index'] = $sheetIndex;
                    $candidate['sheet_name'] = $sheet['name'];
                    $candidate['sheet_path'] = $sheet['path'];
                    $candidates[] = $candidate;
                }
            }
        }

        if ($candidates === []) {
            throw new DepotWorkbookException(
                'V žádném viditelném listu nebyla rozpoznána povinná importní hlavička.',
            );
        }

        usort(
            $candidates,
            static fn (array $left, array $right): int => $right['score'] <=> $left['score'],
        );

        $best = $candidates[0];
        $ties = array_values(array_filter(
            $candidates,
            static fn (array $candidate): bool => $candidate['score'] === $best['score']
                && (
                    $candidate['sheet_index'] !== $best['sheet_index']
                    || $candidate['header_start_row'] !== $best['header_start_row']
                    || $candidate['header_end_row'] !== $best['header_end_row']
                ),
        ));

        if ($ties !== []) {
            throw new DepotWorkbookException(
                'Sešit obsahuje více stejně pravděpodobných importních tabulek; automatický výběr není bezpečný.',
            );
        }

        unset($best['score']);

        $fingerprintSource = [
            'sheet' => $best['sheet_name'],
            'header_start_row' => $best['header_start_row'],
            'header_end_row' => $best['header_end_row'],
            'columns' => $best['columns'],
        ];
        $encoded = json_encode(
            $fingerprintSource,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $best['schema_fingerprint'] = strtoupper(hash('sha256', $encoded));

        return $best;
    }

    /**
     * @param  array<int, array<int, array{
     *     reference:string,
     *     value:?string,
     *     formula:bool
     * }>>  $rowsByNumber
     * @return array{
     *     header_start_row:int,
     *     header_end_row:int,
     *     data_start_row:int,
     *     columns:array<string, array{
     *         column:int,
     *         letter:string,
     *         header:string
     *     }>,
     *     score:int
     * }|null
     */
    private function candidate(
        array $rowsByNumber,
        int $start,
        int $depth,
    ): ?array {
        $headers = [];
        $continuationCount = 0;

        for ($rowNumber = $start; $rowNumber < $start + $depth; $rowNumber++) {
            foreach ($rowsByNumber[$rowNumber] ?? [] as $column => $cell) {
                if ($cell['formula'] || $cell['value'] === null) {
                    continue;
                }

                $value = trim($cell['value']);

                if ($value === '') {
                    continue;
                }

                if (array_key_exists($column, $headers)) {
                    $headers[$column] .= ' '.$value;
                    $continuationCount++;
                } else {
                    $headers[$column] = $value;
                }
            }
        }

        if ($headers === []) {
            return null;
        }

        $columns = [];
        $duplicates = [];

        foreach ($headers as $column => $header) {
            $field = $this->fieldForHeader($header);

            if ($field === null) {
                continue;
            }

            if (array_key_exists($field, $columns)) {
                $duplicates[] = $field;

                continue;
            }

            $columns[$field] = [
                'column' => $column,
                'letter' => $this->columnLetter($column),
                'header' => trim($header),
            ];
        }

        if ($duplicates !== []) {
            return null;
        }

        foreach (self::REQUIRED_FIELDS as $requiredField) {
            if (! array_key_exists($requiredField, $columns)) {
                return null;
            }
        }

        ksort($columns);

        return [
            'header_start_row' => $start,
            'header_end_row' => $start + $depth - 1,
            'data_start_row' => $start + $depth,
            'columns' => $columns,
            'score' => (count(self::REQUIRED_FIELDS) * 10_000)
                + (count($columns) * 100)
                + ($continuationCount * 5)
                + $depth,
        ];
    }

    private function fieldForHeader(string $header): ?string
    {
        $normalized = $this->normalizeHeader($header);

        if ($normalized === '') {
            return null;
        }

        foreach (self::EXACT_ALIASES as $field => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $field;
            }
        }

        foreach (self::PREFIX_ALIASES as $field => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($normalized, $prefix)) {
                    return $field;
                }
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $decomposed = Normalizer::normalize(
            trim($value),
            Normalizer::FORM_D,
        );

        if (is_string($decomposed)) {
            $value = $decomposed;
        }

        $value = preg_replace('/\p{Mn}+/u', '', $value) ?? $value;
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(
            preg_replace('/\s+/u', ' ', $value) ?? '',
        );
    }

    private function columnLetter(int $column): string
    {
        $letter = '';

        while ($column > 0) {
            $column--;
            $letter = chr(65 + ($column % 26)).$letter;
            $column = intdiv($column, 26);
        }

        return $letter;
    }
}
