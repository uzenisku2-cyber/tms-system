<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Exceptions\DepotWorkbookException;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Illuminate\Support\Collection;

final class DepotImportPreviewService
{
    private const MAX_PREVIEW_ROWS = 200;

    private const MAX_DATA_ROWS = 10_000;

    /** @var list<string> */
    private const OPERATIONAL_FIELDS = [
        'departure_time',
        'arrival_time',
        'actual_km',
        'planned_km',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'customer_rejected_parcels',
        'surcharge_amount',
    ];

    public function __construct(
        private readonly DepotWorkbookReader $reader,
        private readonly DepotImportHeaderDetector $headers,
        private readonly DepotCarrierAliasNormalizer $aliases,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function inspect(
        string $path,
        int $organizationId,
    ): array {
        $organization = $this->mainOrganization($organizationId);
        $workbook = $this->reader->read($path);
        $detected = $this->headers->detect($workbook['sheets']);
        $sheet = $workbook['sheets'][$detected['sheet_index']];
        $carrierColumn = $detected['columns']['carrier_name']['column'];
        $carrierCounts = [];
        $dataRowCount = 0;
        $mappedFormulaCellCount = 0;

        foreach ($sheet['rows'] as $row) {
            if ($row['row'] < $detected['data_start_row']) {
                continue;
            }

            if (++$dataRowCount > self::MAX_DATA_ROWS) {
                throw new DepotWorkbookException(
                    'Importní tabulka překročila podporovaný počet datových řádků.',
                );
            }

            foreach ($detected['columns'] as $column) {
                if (($row['cells'][$column['column']]['formula'] ?? false) === true) {
                    $mappedFormulaCellCount++;
                }
            }

            $carrierCell = $row['cells'][$carrierColumn] ?? null;

            if (
                ! is_array($carrierCell)
                || $carrierCell['formula']
                || $carrierCell['value'] === null
            ) {
                continue;
            }

            $carrier = $this->text($carrierCell['value']);

            if ($carrier === '') {
                continue;
            }

            $key = $carrier;
            $carrierCounts[$key] = ($carrierCounts[$key] ?? 0) + 1;
        }

        arsort($carrierCounts);
        $suggestedAlias = (string) $organization->getAttribute('name');
        $suggestedKey = $this->aliases->normalize($suggestedAlias);

        return [
            'source' => [
                'file_sha256' => $workbook['file_sha256'],
                'visible_sheet_count' => count($workbook['sheets']),
                'data_row_count' => $dataRowCount,
                'formula_cell_count' => $workbook['formula_cell_count'],
                'mapped_formula_cell_count' => $mappedFormulaCellCount,
                'stored' => false,
                'read_only' => true,
                'formula_values_used_for_import' => false,
            ],
            'detected' => $this->detectedPayload($detected),
            'organization' => [
                'id' => (int) $organization->getKey(),
                'name' => $suggestedAlias,
                'type' => (string) $organization->getAttribute('type'),
            ],
            'suggested_alias' => $suggestedAlias,
            'normalized_suggested_alias' => $suggestedKey,
            'suggested_matching_row_count' => array_sum(
                array_map(
                    fn (int $count, string $carrier): int => $this->aliases->normalize($carrier) === $suggestedKey
                            ? $count
                            : 0,
                    array_values($carrierCounts),
                    array_keys($carrierCounts),
                ),
            ),
            'carrier_values' => array_values(array_map(
                fn (string $carrier, int $count): array => [
                    'value' => $carrier,
                    'normalized' => $this->aliases->normalize($carrier),
                    'row_count' => $count,
                    'matches_suggestion' => $this->aliases->normalize($carrier) === $suggestedKey,
                ],
                array_keys($carrierCounts),
                array_values($carrierCounts),
            )),
            'confirmation_required' => true,
            'write_performed' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(
        string $path,
        int $organizationId,
        string $confirmedAlias,
    ): array {
        return $this->buildPreview(
            path: $path,
            organizationId: $organizationId,
            confirmedAlias: $confirmedAlias,
            includeAllRows: false,
        );
    }

    /**
     * Returns the complete validated value set for creation of a persistent
     * draft. The workbook itself is still only read and is never stored.
     *
     * @return array<string, mixed>
     */
    public function prepareDraft(
        string $path,
        int $organizationId,
        string $confirmedAlias,
    ): array {
        return $this->buildPreview(
            path: $path,
            organizationId: $organizationId,
            confirmedAlias: $confirmedAlias,
            includeAllRows: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreview(
        string $path,
        int $organizationId,
        string $confirmedAlias,
        bool $includeAllRows,
    ): array {
        $organization = $this->mainOrganization($organizationId);
        $alias = $this->text($confirmedAlias);
        $aliasKey = $this->aliases->normalize($alias);

        if ($alias === '' || $aliasKey === '') {
            throw new DepotWorkbookException(
                'Potvrzený alias hlavního dopravce nesmí být prázdný.',
            );
        }

        $workbook = $this->reader->read($path);
        $detected = $this->headers->detect($workbook['sheets']);
        $sheet = $workbook['sheets'][$detected['sheet_index']];
        $rows = [];
        $sourceDriverCounts = [];
        $matchedCarrierCounts = [];
        $excludedCarrierRowCount = 0;
        $dataRowCount = 0;
        $mappedFormulaCellCount = 0;

        foreach ($sheet['rows'] as $sourceRow) {
            if ($sourceRow['row'] < $detected['data_start_row']) {
                continue;
            }

            if (++$dataRowCount > self::MAX_DATA_ROWS) {
                throw new DepotWorkbookException(
                    'Importní tabulka překročila podporovaný počet datových řádků.',
                );
            }

            $carrier = $this->rawText(
                $sourceRow,
                $detected,
                'carrier_name',
            );

            if ($carrier === '') {
                continue;
            }

            if ($this->aliases->normalize($carrier) !== $aliasKey) {
                $excludedCarrierRowCount++;

                continue;
            }

            $matchedCarrierCounts[$carrier] =
                ($matchedCarrierCounts[$carrier] ?? 0) + 1;

            $row = $this->previewRow(
                $sourceRow,
                $detected,
                $carrier,
            );
            $mappedFormulaCellCount += $row['mapped_formula_cell_count'];
            unset($row['mapped_formula_cell_count']);

            if ($row['source_driver_name'] !== '') {
                $sourceDriverCounts[$row['source_driver_name']] =
                    ($sourceDriverCounts[$row['source_driver_name']] ?? 0) + 1;
            }

            $rows[] = $row;
        }

        $this->markDuplicateRoutes($rows);

        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?string => $row['service_date'],
            $rows,
        )));
        sort($dates);

        $eligibleDrivers = $this->eligibleDrivers(
            $organizationId,
            $dates[0] ?? null,
            $dates === [] ? null : $dates[count($dates) - 1],
        );

        arsort($sourceDriverCounts);
        arsort($matchedCarrierCounts);

        $totals = $this->totals($rows);
        $previewRows = $includeAllRows
            ? $rows
            : array_slice($rows, 0, self::MAX_PREVIEW_ROWS);

        return [
            'source' => [
                'file_sha256' => $workbook['file_sha256'],
                'visible_sheet_count' => count($workbook['sheets']),
                'data_row_count' => $dataRowCount,
                'formula_cell_count' => $workbook['formula_cell_count'],
                'mapped_formula_cell_count' => $mappedFormulaCellCount,
                'stored' => false,
                'read_only' => true,
                'formula_values_used_for_import' => false,
            ],
            'detected' => $this->detectedPayload($detected),
            'organization' => [
                'id' => (int) $organization->getKey(),
                'name' => (string) $organization->getAttribute('name'),
                'type' => (string) $organization->getAttribute('type'),
            ],
            'confirmed_alias' => $alias,
            'normalized_confirmed_alias' => $aliasKey,
            'matched_carrier_values' => array_values(array_map(
                static fn (string $carrier, int $count): array => [
                    'value' => $carrier,
                    'row_count' => $count,
                ],
                array_keys($matchedCarrierCounts),
                array_values($matchedCarrierCounts),
            )),
            'excluded_carrier_row_count' => $excludedCarrierRowCount,
            'period' => [
                'from' => $dates[0] ?? null,
                'until' => $dates === []
                    ? null
                    : $dates[count($dates) - 1],
            ],
            'totals' => $totals,
            'source_driver_values' => array_values(array_map(
                static fn (string $driver, int $count): array => [
                    'value' => $driver,
                    'row_count' => $count,
                    'mapping_status' => 'unmapped',
                ],
                array_keys($sourceDriverCounts),
                array_values($sourceDriverCounts),
            )),
            'eligible_drivers' => $eligibleDrivers,
            'rows' => $previewRows,
            'row_count' => count($rows),
            'rows_truncated' => count($rows) > count($previewRows),
            'confirmation_consumed' => true,
            'import_enabled' => false,
            'write_performed' => false,
        ];
    }

    private function mainOrganization(int $organizationId): Organization
    {
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->firstOrFail();

        if (
            $organization->getAttribute('type') !== Organization::TYPE_MASTER
            || $organization->getAttribute('status') !== Organization::STATUS_ACTIVE
        ) {
            abort(
                403,
                'Náhled importu depa je dostupný pouze aktivní hlavní organizaci.',
            );
        }

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $detected
     * @return array<string, mixed>
     */
    private function detectedPayload(array $detected): array
    {
        return [
            'sheet_name' => $detected['sheet_name'],
            'header_start_row' => $detected['header_start_row'],
            'header_end_row' => $detected['header_end_row'],
            'data_start_row' => $detected['data_start_row'],
            'columns' => $detected['columns'],
            'schema_fingerprint' => $detected['schema_fingerprint'],
        ];
    }

    /**
     * @param  array{
     *     row:int,
     *     cells:array<int, array{
     *         reference:string,
     *         value:?string,
     *         formula:bool
     *     }>
     * }  $sourceRow
     * @param  array<string, mixed>  $detected
     * @return array<string, mixed>
     */
    private function previewRow(
        array $sourceRow,
        array $detected,
        string $carrier,
    ): array {
        $errors = [];
        $warnings = [];
        $formulaFields = [];

        foreach ($detected['columns'] as $field => $column) {
            if (($sourceRow['cells'][$column['column']]['formula'] ?? false) === true) {
                $formulaFields[] = $field;
            }
        }

        if ($formulaFields !== []) {
            $errors[] = 'Zdrojová provozní hodnota je vzorec; řádek nelze importovat.';
        }

        $raw = [];

        foreach (array_keys($detected['columns']) as $field) {
            $raw[$field] = $this->rawText($sourceRow, $detected, $field);
        }

        $serviceDate = $this->parseDate(
            $raw['service_date'] ?? '',
            $errors,
        );
        $routeNumber = $this->text($raw['route_number'] ?? '');
        $driverName = $this->text($raw['source_driver_name'] ?? '');

        if ($routeNumber === '') {
            $errors[] = 'Chybí označení trasy.';
        }

        if ($driverName === '') {
            $errors[] = 'Chybí zdrojové jméno řidiče.';
        }

        $hasOperationalValue = false;

        foreach (self::OPERATIONAL_FIELDS as $field) {
            if ($this->text($raw[$field] ?? '') !== '') {
                $hasOperationalValue = true;
                break;
            }
        }

        $note = $this->text($raw['operational_notes'] ?? '');

        if (! $hasOperationalValue && $note !== '') {
            return [
                'source_row' => $sourceRow['row'],
                'status' => $errors === [] ? 'no_run' : 'invalid',
                'service_date' => $serviceDate,
                'route_number' => $routeNumber,
                'carrier_name' => $carrier,
                'source_driver_name' => $driverName,
                'departure_time' => null,
                'arrival_time' => null,
                'actual_km' => null,
                'planned_km' => null,
                'loaded_parcels' => null,
                'delivered_parcels' => null,
                'redirected_parcels' => null,
                'customer_rejected_parcels' => null,
                'computed_not_delivered_parcels' => null,
                'surcharge_amount' => null,
                'operational_notes' => $note,
                'errors' => $errors,
                'warnings' => [
                    'Řádek neobsahuje provozní hodnoty a bude veden pouze jako neodjetá trasa.',
                ],
                'mapped_formula_cell_count' => count($formulaFields),
            ];
        }

        $loaded = $this->parseParcelCount(
            $raw['loaded_parcels'] ?? '',
            'Naloženo',
            $errors,
            true,
        );
        $delivered = $this->parseParcelCount(
            $raw['delivered_parcels'] ?? '',
            'Doručeno na adresu',
            $errors,
            true,
        );
        $redirected = $this->parseParcelCount(
            $raw['redirected_parcels'] ?? '',
            'Doručeno na výdejní místo',
            $errors,
            true,
        );
        $rejected = $this->parseParcelCount(
            $raw['customer_rejected_parcels'] ?? '',
            'Odmítnuto zákazníkem',
            $errors,
            true,
        );
        $computedNotDelivered = null;

        if (
            $loaded !== null
            && $delivered !== null
            && $redirected !== null
            && $rejected !== null
        ) {
            $computedNotDelivered = $loaded
                - $delivered
                - $redirected
                - $rejected;

            if ($computedNotDelivered < 0) {
                $errors[] = 'Součet koncových stavů zásilek je vyšší než počet naložených zásilek.';
            }
        }

        $actualKm = $this->parseDecimalHundredths(
            $raw['actual_km'] ?? '',
            'Naměřené kilometry',
            $errors,
        );
        $plannedKm = $this->parseDecimalHundredths(
            $raw['planned_km'] ?? '',
            'Plánované kilometry',
            $errors,
        );
        $surcharge = $this->parseDecimalHundredths(
            $raw['surcharge_amount'] ?? '',
            'Příplatek',
            $errors,
        );

        if ($surcharge !== null && $surcharge > 0 && $note === '') {
            $errors[] = 'Poznámka je povinná, pokud je příplatek vyšší než nula.';
        }

        $departure = $this->parseTime(
            $raw['departure_time'] ?? '',
            'Čas odjezdu',
            $errors,
        );
        $arrival = $this->parseTime(
            $raw['arrival_time'] ?? '',
            'Čas příjezdu',
            $errors,
        );

        if ($serviceDate !== null) {
            $this->validateCalendarColumns(
                $serviceDate,
                $raw,
                $errors,
            );
        }

        return [
            'source_row' => $sourceRow['row'],
            'status' => $errors === [] ? 'ready' : 'invalid',
            'service_date' => $serviceDate,
            'route_number' => $routeNumber,
            'carrier_name' => $carrier,
            'source_driver_name' => $driverName,
            'departure_time' => $departure,
            'arrival_time' => $arrival,
            'actual_km' => $this->decimalString($actualKm),
            'planned_km' => $this->decimalString($plannedKm),
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $delivered,
            'redirected_parcels' => $redirected,
            'customer_rejected_parcels' => $rejected,
            'computed_not_delivered_parcels' => $computedNotDelivered,
            'surcharge_amount' => $this->decimalString($surcharge),
            'operational_notes' => $note === '' ? null : $note,
            'errors' => array_values(array_unique($errors)),
            'warnings' => $warnings,
            'mapped_formula_cell_count' => count($formulaFields),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function markDuplicateRoutes(array &$rows): void
    {
        $keys = [];

        foreach ($rows as $index => $row) {
            if (
                $row['status'] !== 'ready'
                || ! is_string($row['service_date'])
                || $row['service_date'] === ''
                || $row['route_number'] === ''
            ) {
                continue;
            }

            $key = $row['service_date'].'|'.mb_strtolower(
                trim((string) $row['route_number']),
                'UTF-8',
            );
            $keys[$key][] = $index;
        }

        foreach ($keys as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $rows[$index]['status'] = 'invalid';
                $rows[$index]['errors'][] =
                    'Stejné datum a trasa jsou v sešitu uvedeny vícekrát.';
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int|string>
     */
    private function totals(array $rows): array
    {
        $totals = [
            'matched_rows' => count($rows),
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
            $statusKey = match ($row['status']) {
                'ready' => 'ready_rows',
                'no_run' => 'no_run_rows',
                default => 'invalid_rows',
            };
            $totals[$statusKey]++;

            if ($row['status'] !== 'ready') {
                continue;
            }

            foreach ([
                'loaded_parcels',
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
                'computed_not_delivered_parcels',
            ] as $field) {
                $totals[$field] += (int) ($row[$field] ?? 0);
            }

            $totals['actual_km_hundredths'] +=
                $this->stringToHundredths($row['actual_km']);
            $totals['planned_km_hundredths'] +=
                $this->stringToHundredths($row['planned_km']);
            $totals['surcharge_hundredths'] +=
                $this->stringToHundredths($row['surcharge_amount']);
        }

        $totals['actual_km'] = $this->decimalString(
            $totals['actual_km_hundredths'],
        ) ?? '0.00';
        $totals['planned_km'] = $this->decimalString(
            $totals['planned_km_hundredths'],
        ) ?? '0.00';
        $totals['surcharge_amount'] = $this->decimalString(
            $totals['surcharge_hundredths'],
        ) ?? '0.00';

        unset(
            $totals['actual_km_hundredths'],
            $totals['planned_km_hundredths'],
            $totals['surcharge_hundredths'],
        );

        return $totals;
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    private function eligibleDrivers(
        int $organizationId,
        ?string $from,
        ?string $until,
    ): array {
        if ($from === null || $until === null) {
            return [];
        }

        /** @var Collection<int, DriverOrganizationAssignment> $assignments */
        $assignments = DriverOrganizationAssignment::query()
            ->where('organization_id', $organizationId)
            ->whereDate('valid_from', '<=', $until)
            ->where(function ($query) use ($from): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $from);
            })
            ->whereHas('driver', function ($query): void {
                $query
                    ->where('status', Driver::STATUS_ACTIVE)
                    ->where('active', true);
            })
            ->with('driver')
            ->orderBy('driver_id')
            ->orderBy('valid_from')
            ->get();

        return $assignments
            ->map(static function (DriverOrganizationAssignment $assignment): array {
                $driver = $assignment->driver;
                $validFrom = $assignment->getAttribute('valid_from');
                $validUntil = $assignment->getAttribute('valid_until');

                return [
                    'assignment_id' => (int) $assignment->getKey(),
                    'driver_id' => (int) $assignment->getAttribute('driver_id'),
                    'driver_name' => $driver instanceof Driver
                        ? $driver->full_name
                        : '',
                    'external_driver_id' => $driver instanceof Driver
                        ? $driver->getAttribute('external_driver_id')
                        : null,
                    'valid_from' => $validFrom instanceof DateTimeInterface
                        ? $validFrom->format('Y-m-d')
                        : (string) $validFrom,
                    'valid_until' => $validUntil === null
                        ? null
                        : ($validUntil instanceof DateTimeInterface
                            ? $validUntil->format('Y-m-d')
                            : (string) $validUntil),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{row:int, cells:array<int, array<string, mixed>>}  $row
     * @param  array<string, mixed>  $detected
     */
    private function rawText(
        array $row,
        array $detected,
        string $field,
    ): string {
        $column = $detected['columns'][$field]['column'] ?? null;

        if (! is_int($column)) {
            return '';
        }

        $value = $row['cells'][$column]['value'] ?? null;

        return is_string($value)
            ? $this->text($value)
            : '';
    }

    private function text(string $value): string
    {
        $value = str_replace("\u{00A0}", ' ', $value);

        return trim(
            preg_replace('/\s+/u', ' ', $value) ?? '',
        );
    }

    /** @param  list<string>  $errors */
    private function parseDate(string $value, array &$errors): ?string
    {
        $value = $this->text($value);

        if ($value === '') {
            $errors[] = 'Chybí datum trasy.';

            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial >= 1 && $serial <= 2_958_465) {
                return CarbonImmutable::create(
                    1899,
                    12,
                    30,
                    0,
                    0,
                    0,
                    'UTC',
                )
                    ->addDays((int) floor($serial))
                    ->toDateString();
            }
        }

        foreach (['Y-m-d', 'd.m.Y', 'd/m/Y', 'j.n.Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat(
                    '!'.$format,
                    $value,
                    'UTC',
                );
            } catch (InvalidFormatException) {
                continue;
            }

            $lastErrors = CarbonImmutable::getLastErrors();

            if (
                $date !== null
                && (
                    $lastErrors === false
                    || (
                        $lastErrors['warning_count'] === 0
                        && $lastErrors['error_count'] === 0
                    )
                )
            ) {
                return $date->toDateString();
            }
        }

        $errors[] = 'Datum trasy nemá podporovaný formát.';

        return null;
    }

    /** @param  list<string>  $errors */
    private function parseTime(
        string $value,
        string $label,
        array &$errors,
    ): ?string {
        $value = $this->text($value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $fraction = (float) $value;

            if ($fraction >= 0 && $fraction < 1) {
                $minutes = (int) round($fraction * 1440) % 1440;

                return sprintf(
                    '%02d:%02d',
                    intdiv($minutes, 60),
                    $minutes % 60,
                );
            }
        }

        if (preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1) {
            [$hour, $minute] = array_map(
                'intval',
                array_slice(explode(':', $value), 0, 2),
            );

            return sprintf('%02d:%02d', $hour, $minute);
        }

        $errors[] = "{$label} nemá podporovaný formát.";

        return null;
    }

    /** @param  list<string>  $errors */
    private function parseParcelCount(
        string $value,
        string $label,
        array &$errors,
        bool $blankAsZero,
    ): ?int {
        $value = $this->numericText($value);

        if ($value === '') {
            return $blankAsZero ? 0 : null;
        }

        if (! is_numeric($value)) {
            $errors[] = "{$label} musí být celé nezáporné číslo.";

            return null;
        }

        $number = (float) $value;

        if (
            ! is_finite($number)
            || $number < 0
            || $number > 2_147_483_647
            || floor($number) !== $number
        ) {
            $errors[] = "{$label} musí být celé nezáporné číslo.";

            return null;
        }

        return (int) $number;
    }

    /** @param  list<string>  $errors */
    private function parseDecimalHundredths(
        string $value,
        string $label,
        array &$errors,
    ): ?int {
        $value = $this->numericText($value);

        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            $errors[] = "{$label} musí být nezáporné číslo.";

            return null;
        }

        $number = (float) $value;

        if (! is_finite($number) || $number < 0 || $number > 99_999_999.99) {
            $errors[] = "{$label} musí být nezáporné číslo v podporovaném rozsahu.";

            return null;
        }

        return (int) round($number * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function numericText(string $value): string
    {
        $value = str_replace(
            ["\u{00A0}", ' '],
            '',
            trim($value),
        );

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $raw
     * @param  list<string>  $errors
     */
    private function validateCalendarColumns(
        string $serviceDate,
        array $raw,
        array &$errors,
    ): void {
        $date = CarbonImmutable::parse($serviceDate, 'UTC');

        if (isset($raw['year']) && $this->text($raw['year']) !== '') {
            $year = $this->parseUnsignedInteger($raw['year']);

            if ($year === null || $year !== $date->year) {
                $errors[] = 'Sloupec Rok nesouhlasí s datem trasy.';
            }
        }

        if (isset($raw['month']) && $this->text($raw['month']) !== '') {
            $month = $this->parseUnsignedInteger($raw['month']);

            if ($month === null || $month !== $date->month) {
                $errors[] = 'Sloupec Měsíc nesouhlasí s datem trasy.';
            }
        }
    }

    private function parseUnsignedInteger(string $value): ?int
    {
        $value = $this->numericText($value);

        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number >= 0 && floor($number) === $number
            ? (int) $number
            : null;
    }

    private function decimalString(?int $hundredths): ?string
    {
        if ($hundredths === null) {
            return null;
        }

        return sprintf(
            '%d.%02d',
            intdiv($hundredths, 100),
            abs($hundredths % 100),
        );
    }

    private function stringToHundredths(mixed $value): int
    {
        if (! is_string($value) || $value === '') {
            return 0;
        }

        return (int) round((float) $value * 100);
    }
}
