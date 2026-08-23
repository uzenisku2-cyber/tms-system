<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\Drivers\Models\Driver;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use LogicException;

final class DepotDriverRecordReviewService
{
    public const STATUS_MATCHING = 'matching';

    public const STATUS_DIFFERENT = 'different';

    public const STATUS_MISSING_DRIVER_RECORD = 'missing_driver_record';

    public const STATUS_DRIVER_MISMATCH = 'driver_mismatch';

    public const STATUS_NOT_COMPARABLE = 'not_comparable';

    /** @var list<string> */
    public const COMPARISON_STATUSES = [
        self::STATUS_MATCHING,
        self::STATUS_DIFFERENT,
        self::STATUS_MISSING_DRIVER_RECORD,
        self::STATUS_DRIVER_MISMATCH,
        self::STATUS_NOT_COMPARABLE,
    ];

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'departure_time' => 'Čas odjezdu',
        'arrival_time' => 'Čas příjezdu',
        'loaded_parcels' => 'Naloženo',
        'delivered_parcels' => 'Doručeno na adresu',
        'redirected_parcels' => 'Doručeno na výdejní místo',
        'customer_rejected_parcels' => 'Odmítnuto zákazníkem',
        'computed_not_delivered_parcels' => 'Nedoručeno',
        'actual_km' => 'Skutečné kilometry',
        'planned_km' => 'Plánované kilometry',
        'surcharge_amount' => 'Příplatek',
        'operational_notes' => 'Provozní poznámka',
    ];

    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DepotImportIntegrityService $integrity,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function compare(
        string $batchPublicId,
        array $filters,
    ): array {
        $organizationId = $this->organizationContext->requireId();
        $batch = DepotImportBatch::query()
            ->where('organization_id', $organizationId)
            ->where('status', DepotImportBatch::STATUS_IMPORTED)
            ->where('public_id', $batchPublicId)
            ->firstOrFail();

        /** @var EloquentCollection<int, DepotImportRow> $sourceRows */
        $sourceRows = $batch->rows()
            ->with('assignedDriver')
            ->orderByDesc('service_date')
            ->orderBy('route_number_normalized')
            ->orderBy('source_row')
            ->get();

        $this->integrity->assertBatchIntegrity(
            $batch,
            $sourceRows,
        );

        $filteredRows = $this->filterRows(
            $sourceRows,
            $filters,
        );
        $driverRecords = $this->driverRecordsByKey(
            $organizationId,
            $filteredRows,
        );
        $comparisons = $filteredRows
            ->map(
                fn (DepotImportRow $row): array => $this->compareRow(
                    $batch,
                    $row,
                    $driverRecords,
                ),
            )
            ->values();
        $summary = $this->summary($comparisons);

        $status = $filters['comparison_status'] ?? null;

        if (is_string($status) && $status !== '') {
            $comparisons = $comparisons
                ->filter(
                    static fn (array $item): bool => ($item[
                        'comparison_status'
                    ] ?? null) === $status,
                )
                ->values();
        }

        $page = $this->positiveInteger(
            $filters['page'] ?? null,
            1,
        );
        $perPage = min(
            100,
            $this->positiveInteger(
                $filters['per_page'] ?? null,
                50,
            ),
        );
        $total = $comparisons->count();
        $lastPage = max(
            1,
            (int) ceil($total / $perPage),
        );
        $items = $comparisons
            ->forPage($page, $perPage)
            ->values();

        return [
            'workspace' => 'depot_driver_record_review',
            'batch' => $this->batchPayload($batch),
            'contract' => [
                'read_only' => true,
                'matching_key' => [
                    'organization_id',
                    'service_date',
                    'route_number_normalized',
                ],
                'assigned_driver_must_match' => true,
                'depot_source_integrity_verified' => true,
                'depot_source_values_changed' => false,
                'daily_report_values_changed' => false,
                'reconciliation_records_created' => 0,
                'depot_only_fields' => [],
            ],
            'filters' => $this->filterPayload($filters),
            'summary' => $summary,
            'items' => $items->all(),
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $items->isEmpty()
                    ? null
                    : (($page - 1) * $perPage) + 1,
                'to' => $items->isEmpty()
                    ? null
                    : (($page - 1) * $perPage) + $items->count(),
            ],
            'capabilities' => [
                'quick_accept_available' => false,
                'route_split_available' => false,
                'depot_source_revision_available' => false,
            ],
        ];
    }

    /**
     * @param  EloquentCollection<int, DepotImportRow>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DepotImportRow>
     */
    private function filterRows(
        EloquentCollection $rows,
        array $filters,
    ): Collection {
        $dateFrom = $filters['service_date_from'] ?? null;
        $dateUntil = $filters['service_date_to'] ?? null;
        $driverId = $filters['performed_by_driver_id'] ?? null;
        $routeFilter = $filters['route_number'] ?? null;
        $normalizedRouteFilter = is_string($routeFilter)
            ? mb_strtolower(trim($routeFilter), 'UTF-8')
            : '';

        return $rows
            ->filter(
                function (DepotImportRow $row) use (
                    $dateFrom,
                    $dateUntil,
                    $driverId,
                    $normalizedRouteFilter,
                ): bool {
                    $serviceDate = $this->dateValue(
                        $row->getAttribute('service_date'),
                    );

                    if (
                        is_string($dateFrom)
                        && $dateFrom !== ''
                        && $serviceDate < $dateFrom
                    ) {
                        return false;
                    }

                    if (
                        is_string($dateUntil)
                        && $dateUntil !== ''
                        && $serviceDate > $dateUntil
                    ) {
                        return false;
                    }

                    if (
                        is_int($driverId)
                        && $driverId > 0
                        && (int) $row->getAttribute(
                            'assigned_driver_id',
                        ) !== $driverId
                    ) {
                        return false;
                    }

                    if (
                        $normalizedRouteFilter !== ''
                        && ! str_contains(
                            (string) $row->getAttribute(
                                'route_number_normalized',
                            ),
                            $normalizedRouteFilter,
                        )
                    ) {
                        return false;
                    }

                    return true;
                },
            )
            ->values();
    }

    /**
     * @param  Collection<int, DepotImportRow>  $rows
     * @return Collection<string, Collection<int, DailyReport>>
     */
    private function driverRecordsByKey(
        int $organizationId,
        Collection $rows,
    ): Collection {
        $readyRows = $rows->filter(
            static fn (DepotImportRow $row): bool => $row->getAttribute(
                'status',
            ) === DepotImportRow::STATUS_READY,
        );

        if ($readyRows->isEmpty()) {
            return collect();
        }

        $dates = $readyRows
            ->map(
                fn (DepotImportRow $row): string => $this->dateValue(
                    $row->getAttribute('service_date'),
                ),
            )
            ->unique()
            ->values()
            ->all();
        $routes = $readyRows
            ->map(
                static fn (DepotImportRow $row): string => (string) $row
                    ->getAttribute('route_number_normalized'),
            )
            ->unique()
            ->values()
            ->all();

        /** @var EloquentCollection<int, DailyReport> $reports */
        $reports = DailyReport::query()
            ->with('performedByDriver')
            ->forOrganization($organizationId)
            ->where(
                static function (Builder $query) use ($dates): void {
                    foreach ($dates as $date) {
                        $query->orWhereDate('service_date', $date);
                    }
                },
            )
            ->whereIn('route_number_normalized', $routes)
            ->orderBy('id')
            ->get();

        /** @var Collection<string, Collection<int, DailyReport>> $driverRecords */
        $driverRecords = $reports
            ->toBase()
            ->groupBy(
                fn (DailyReport $report): string => $this->recordKey(
                    $this->dateValue(
                        $report->getAttribute('service_date'),
                    ),
                    (string) $report->getAttribute(
                        'route_number_normalized',
                    ),
                ),
            );

        return $driverRecords;
    }

    /**
     * @param  Collection<string, Collection<int, DailyReport>>  $driverRecords
     * @return array<string, mixed>
     */
    private function compareRow(
        DepotImportBatch $batch,
        DepotImportRow $row,
        Collection $driverRecords,
    ): array {
        $depot = $this->depotPayload($batch, $row);

        if (
            $row->getAttribute('status')
            === DepotImportRow::STATUS_NO_RUN
        ) {
            return $this->comparisonPayload(
                self::STATUS_NOT_COMPARABLE,
                'depot_no_run',
                $depot,
                null,
                [],
            );
        }

        $assignedDriverId = $row->getAttribute(
            'assigned_driver_id',
        );

        if ($assignedDriverId === null) {
            return $this->comparisonPayload(
                self::STATUS_NOT_COMPARABLE,
                'depot_driver_unassigned',
                $depot,
                null,
                [],
            );
        }

        $key = $this->recordKey(
            $this->dateValue(
                $row->getAttribute('service_date'),
            ),
            (string) $row->getAttribute(
                'route_number_normalized',
            ),
        );
        $records = $driverRecords->get($key);

        if (! $records instanceof Collection || $records->isEmpty()) {
            return $this->comparisonPayload(
                self::STATUS_MISSING_DRIVER_RECORD,
                'driver_record_missing',
                $depot,
                null,
                [],
            );
        }

        if ($records->count() !== 1) {
            return $this->comparisonPayload(
                self::STATUS_NOT_COMPARABLE,
                'multiple_driver_records',
                $depot,
                null,
                [],
            );
        }

        $report = $records->first();

        $driver = $this->driverPayload($report);

        if (
            (int) $report->getAttribute(
                'performed_by_driver_id',
            ) !== (int) $assignedDriverId
        ) {
            return $this->comparisonPayload(
                self::STATUS_DRIVER_MISMATCH,
                'assigned_driver_differs',
                $depot,
                $driver,
                [[
                    'field' => 'performed_by_driver_id',
                    'label' => 'Přiřazený řidič',
                    'depot_value' => (int) $assignedDriverId,
                    'driver_value' => (int) $report->getAttribute(
                        'performed_by_driver_id',
                    ),
                ]],
            );
        }

        $depotValues = $depot['values'] ?? null;
        $driverValues = $driver['values'] ?? null;

        if (! is_array($depotValues) || ! is_array($driverValues)) {
            throw new LogicException(
                'Comparable record values are unavailable.',
            );
        }

        $differences = $this->valueDifferences(
            $depotValues,
            $driverValues,
        );

        return $this->comparisonPayload(
            $differences === []
                ? self::STATUS_MATCHING
                : self::STATUS_DIFFERENT,
            $differences === []
                ? 'all_comparable_values_match'
                : 'comparable_values_differ',
            $depot,
            $driver,
            $differences,
        );
    }

    /** @return array<string, mixed> */
    private function batchPayload(DepotImportBatch $batch): array
    {
        return [
            'public_id' => (string) $batch->getAttribute('public_id'),
            'status' => (string) $batch->getAttribute('status'),
            'original_filename' => (string) $batch->getAttribute(
                'original_filename',
            ),
            'confirmed_carrier_alias' => (string) $batch->getAttribute(
                'confirmed_carrier_alias',
            ),
            'period_from' => $this->dateValue(
                $batch->getAttribute('period_from'),
            ),
            'period_until' => $this->dateValue(
                $batch->getAttribute('period_until'),
            ),
            'source_record_count' => (int) $batch->getAttribute(
                'row_count',
            ),
            'protected_totals_sha256' => (string) $batch->getAttribute(
                'protected_totals_sha256',
            ),
            'source_records_locked' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function depotPayload(
        DepotImportBatch $batch,
        DepotImportRow $row,
    ): array {
        $assignedDriver = $row->assignedDriver;
        $serviceDate = $this->dateValue(
            $row->getAttribute('service_date'),
        );

        return [
            'batch_public_id' => (string) $batch->getAttribute('public_id'),
            'row_public_id' => (string) $row->getAttribute('public_id'),
            'source_row' => (int) $row->getAttribute('source_row'),
            'source_status' => (string) $row->getAttribute('status'),
            'service_date' => $serviceDate,
            'service_date_display' => CarbonImmutable::parse(
                $serviceDate,
            )->format('d.m.Y'),
            'route_number' => (string) $row->getAttribute('route_number'),
            'source_driver_name' => (string) $row->getAttribute(
                'source_driver_name',
            ),
            'assigned_driver' => $assignedDriver instanceof Driver
                ? [
                    'id' => (int) $assignedDriver->getKey(),
                    'name' => $assignedDriver->full_name,
                    'assignment_id' => (int) $row->getAttribute(
                        'assigned_driver_organization_assignment_id',
                    ),
                ]
                : null,
            'values' => $this->depotValues($row),
            'protected_values_sha256' => (string) $row->getAttribute(
                'protected_values_sha256',
            ),
            'read_only' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function driverPayload(DailyReport $report): array
    {
        $performedByDriver = $report->performedByDriver;

        return [
            'public_id' => (string) $report->getAttribute('public_id'),
            'status' => (string) $report->getAttribute('status'),
            'current_version' => (int) $report->getAttribute(
                'current_version',
            ),
            'entry_method' => (string) $report->getAttribute(
                'entry_method',
            ),
            'performed_by_driver' => [
                'id' => (int) $report->getAttribute(
                    'performed_by_driver_id',
                ),
                'name' => $performedByDriver instanceof Driver
                    ? $performedByDriver->full_name
                    : null,
            ],
            'values' => $this->driverValues($report),
            'editable_here' => false,
        ];
    }

    /** @return array<string, int|string|null> */
    private function depotValues(DepotImportRow $row): array
    {
        return [
            'departure_time' => $this->timeValue(
                $row->getAttribute('departure_time'),
            ),
            'arrival_time' => $this->timeValue(
                $row->getAttribute('arrival_time'),
            ),
            'loaded_parcels' => $this->integerValue(
                $row->getAttribute('loaded_parcels'),
            ),
            'delivered_parcels' => $this->integerValue(
                $row->getAttribute('delivered_parcels'),
            ),
            'redirected_parcels' => $this->integerValue(
                $row->getAttribute('redirected_parcels'),
            ),
            'customer_rejected_parcels' => $this->integerValue(
                $row->getAttribute('customer_rejected_parcels'),
            ),
            'computed_not_delivered_parcels' => $this->integerValue(
                $row->getAttribute('computed_not_delivered_parcels'),
            ),
            'actual_km' => $this->decimalValue(
                $row->getAttribute('actual_km'),
            ),
            'planned_km' => $this->decimalValue(
                $row->getAttribute('planned_km'),
            ),
            'surcharge_amount' => $this->decimalValue(
                $row->getAttribute('surcharge_amount'),
            ),
            'operational_notes' => $this->textValue(
                $row->getAttribute('operational_notes'),
            ),
        ];
    }

    /** @return array<string, int|string|null> */
    private function driverValues(DailyReport $report): array
    {
        $loaded = $this->integerValue(
            $report->getAttribute('loaded_parcels'),
        );
        $delivered = $this->integerValue(
            $report->getAttribute('delivered_parcels'),
        );
        $redirected = $this->integerValue(
            $report->getAttribute('redirected_parcels'),
        );
        $rejected = $this->integerValue(
            $report->getAttribute('undelivered_parcels'),
        );
        $computedNotDelivered = $loaded !== null
            && $delivered !== null
            && $redirected !== null
            && $rejected !== null
                ? $loaded - $delivered - $redirected - $rejected
                : null;

        return [
            'departure_time' => $this->timeValue(
                $report->getAttribute('departure_time'),
            ),
            'arrival_time' => $this->timeValue(
                $report->getAttribute('arrival_time'),
            ),
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $delivered,
            'redirected_parcels' => $redirected,
            'customer_rejected_parcels' => $rejected,
            'computed_not_delivered_parcels' => $computedNotDelivered,
            'actual_km' => $this->decimalValue(
                $report->getAttribute('actual_km'),
            ),
            'planned_km' => $this->decimalValue(
                $report->getAttribute('planned_km'),
            ),
            'surcharge_amount' => $this->decimalValue(
                $report->getAttribute('surcharge_amount'),
            ),
            'operational_notes' => $this->textValue(
                $report->getAttribute('operational_notes'),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $depotValues
     * @param  array<string, mixed>  $driverValues
     * @return list<array<string, mixed>>
     */
    private function valueDifferences(
        array $depotValues,
        array $driverValues,
    ): array {
        $differences = [];

        foreach (self::FIELD_LABELS as $field => $label) {
            $depotValue = $depotValues[$field] ?? null;
            $driverValue = $driverValues[$field] ?? null;

            if ($depotValue === $driverValue) {
                continue;
            }

            $differences[] = [
                'field' => $field,
                'label' => $label,
                'depot_value' => $depotValue,
                'driver_value' => $driverValue,
            ];
        }

        return $differences;
    }

    /**
     * @param  array<string, mixed>  $depot
     * @param  array<string, mixed>|null  $driver
     * @param  list<array<string, mixed>>  $differences
     * @return array<string, mixed>
     */
    private function comparisonPayload(
        string $status,
        string $reason,
        array $depot,
        ?array $driver,
        array $differences,
    ): array {
        return [
            'comparison_status' => $status,
            'comparison_reason' => $reason,
            'difference_count' => count($differences),
            'differences' => $differences,
            'depot_record' => $depot,
            'driver_record' => $driver,
            'actions' => [
                'quick_accept_available' => false,
                'route_split_available' => false,
                'depot_source_revision_available' => false,
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $comparisons
     * @return array<string, int>
     */
    private function summary(Collection $comparisons): array
    {
        $summary = [
            'source_records' => $comparisons->count(),
            self::STATUS_MATCHING => 0,
            self::STATUS_DIFFERENT => 0,
            self::STATUS_MISSING_DRIVER_RECORD => 0,
            self::STATUS_DRIVER_MISMATCH => 0,
            self::STATUS_NOT_COMPARABLE => 0,
            'difference_fields' => 0,
        ];

        foreach ($comparisons as $item) {
            $status = $item['comparison_status'] ?? null;

            if (is_string($status) && array_key_exists($status, $summary)) {
                $summary[$status]++;
            }

            $summary['difference_fields'] += (int) (
                $item['difference_count'] ?? 0
            );
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filterPayload(array $filters): array
    {
        return [
            'comparison_status' => $filters['comparison_status'] ?? null,
            'performed_by_driver_id' => $filters[
                'performed_by_driver_id'
            ] ?? null,
            'service_date_from' => $filters['service_date_from'] ?? null,
            'service_date_to' => $filters['service_date_to'] ?? null,
            'route_number' => $filters['route_number'] ?? null,
        ];
    }

    private function recordKey(
        string $serviceDate,
        string $normalizedRouteNumber,
    ): string {
        return $serviceDate."\x1F".$normalizedRouteNumber;
    }

    private function dateValue(mixed $value): string
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : (string) $value;
    }

    private function integerValue(mixed $value): ?int
    {
        return $value === null || $value === ''
            ? null
            : (int) $value;
    }

    private function decimalValue(mixed $value): ?string
    {
        return $value === null || $value === ''
            ? null
            : number_format(
                (float) $value,
                2,
                '.',
                '',
            );
    }

    private function timeValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $time = trim((string) $value);

        return preg_match('/^\d{2}:\d{2}/', $time) === 1
            ? substr($time, 0, 5)
            : $time;
    }

    private function textValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(
            str_replace(
                ["\r\n", "\r"],
                "\n",
                (string) $value,
            ),
        );

        return $text === '' ? null : $text;
    }

    private function positiveInteger(
        mixed $value,
        int $default,
    ): int {
        return is_int($value) && $value > 0
            ? $value
            : $default;
    }
}
