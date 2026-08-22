<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportEvent;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class DepotImportDraftService
{
    private const MAX_RETURNED_ROWS = 500;

    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DepotImportPreviewService $previews,
        private readonly DepotCarrierAliasNormalizer $aliases,
        private readonly RouteNumberNormalizer $routeNumbers,
        private readonly DepotImportIntegrityService $integrity,
    ) {}

    public function create(
        User $actor,
        string $workbookPath,
        string $originalFilename,
        string $confirmedAlias,
    ): DepotImportBatch {
        $organizationId = $this->organizationContext->requireId();
        $actorId = $this->actorId($actor);
        $prepared = $this->previews->prepareDraft(
            $workbookPath,
            $organizationId,
            $confirmedAlias,
        );
        $totals = $this->arrayValue($prepared, 'totals');
        $source = $this->arrayValue($prepared, 'source');
        $detected = $this->arrayValue($prepared, 'detected');
        $period = $this->arrayValue($prepared, 'period');
        $rows = $prepared['rows'] ?? null;

        if (
            ! is_array($rows)
            || ($prepared['rows_truncated'] ?? true) !== false
        ) {
            throw new LogicException(
                'The complete depot-import value set is unavailable.',
            );
        }

        if ((int) ($totals['invalid_rows'] ?? -1) !== 0) {
            throw ValidationException::withMessages([
                'workbook' => [
                    'Koncept nelze vytvořit, dokud náhled obsahuje chybné řádky.',
                ],
            ]);
        }

        if ((int) ($totals['ready_rows'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'workbook' => [
                    'Sešit neobsahuje žádnou připravenou trasu zvoleného dopravce.',
                ],
            ]);
        }

        $periodFrom = $this->requiredString($period, 'from');
        $periodUntil = $this->requiredString($period, 'until');
        $sourceHash = $this->requiredHash($source, 'file_sha256');
        $schemaFingerprint = $this->requiredHash(
            $detected,
            'schema_fingerprint',
        );
        $normalizedAlias = $this->requiredString(
            $prepared,
            'normalized_confirmed_alias',
        );
        $alias = $this->requiredString(
            $prepared,
            'confirmed_alias',
        );
        $totalsHash = $this->integrity->totalsHash($totals);
        $filename = $this->filename($originalFilename);

        try {
            return DB::transaction(
                function () use (
                    $organizationId,
                    $actorId,
                    $prepared,
                    $totals,
                    $sourceHash,
                    $schemaFingerprint,
                    $normalizedAlias,
                    $alias,
                    $periodFrom,
                    $periodUntil,
                    $totalsHash,
                    $filename,
                    $rows,
                    $detected,
                ): DepotImportBatch {
                    $duplicate = DepotImportBatch::query()
                        ->where('organization_id', $organizationId)
                        ->where('source_sha256', $sourceHash)
                        ->where(
                            'confirmed_carrier_alias_normalized',
                            $normalizedAlias,
                        )
                        ->lockForUpdate()
                        ->first();

                    if ($duplicate instanceof DepotImportBatch) {
                        throw ValidationException::withMessages([
                            'workbook' => [
                                sprintf(
                                    'Stejný sešit a alias už jsou evidovány v dávce %s.',
                                    (string) $duplicate->getAttribute('public_id'),
                                ),
                            ],
                        ]);
                    }

                    $batch = DepotImportBatch::query()->create([
                        'organization_id' => $organizationId,
                        'created_by_user_id' => $actorId,
                        'status' => DepotImportBatch::STATUS_DRAFT,
                        'lock_version' => 1,
                        'original_filename' => $filename,
                        'source_sha256' => $sourceHash,
                        'schema_fingerprint' => $schemaFingerprint,
                        'sheet_name' => $this->requiredString(
                            $detected,
                            'sheet_name',
                        ),
                        'header_start_row' => $this->positiveInteger(
                            $detected,
                            'header_start_row',
                        ),
                        'header_end_row' => $this->positiveInteger(
                            $detected,
                            'header_end_row',
                        ),
                        'data_start_row' => $this->positiveInteger(
                            $detected,
                            'data_start_row',
                        ),
                        'confirmed_carrier_alias' => $alias,
                        'confirmed_carrier_alias_normalized' => $normalizedAlias,
                        'period_from' => $periodFrom,
                        'period_until' => $periodUntil,
                        'row_count' => (int) $prepared['row_count'],
                        'ready_row_count' => (int) $totals['ready_rows'],
                        'no_run_row_count' => (int) $totals['no_run_rows'],
                        'excluded_carrier_row_count' => (int) $prepared[
                            'excluded_carrier_row_count'
                        ],
                        'source_driver_count' => count(
                            $this->arrayValue(
                                $prepared,
                                'source_driver_values',
                            ),
                        ),
                        'unassigned_ready_row_count' => (int) $totals[
                            'ready_rows'
                        ],
                        'source_totals' => $totals,
                        'protected_totals_sha256' => $totalsHash,
                    ]);

                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            throw new LogicException(
                                'A prepared depot-import row is invalid.',
                            );
                        }

                        $attributes = $this->rowAttributes(
                            (int) $batch->getKey(),
                            $row,
                        );
                        $attributes['protected_values_sha256'] =
                            $this->integrity->protectedRowHash($attributes);

                        DepotImportRow::query()->create($attributes);
                    }

                    /** @var EloquentCollection<int, DepotImportRow> $storedRows */
                    $storedRows = $batch->rows()
                        ->orderBy('source_row')
                        ->get();

                    $this->integrity->assertBatchIntegrity(
                        $batch,
                        $storedRows,
                    );

                    DepotImportEvent::query()->create([
                        'depot_import_batch_id' => $batch->getKey(),
                        'depot_import_row_id' => null,
                        'organization_id' => $organizationId,
                        'event_type' => DepotImportEvent::TYPE_DRAFT_CREATED,
                        'acted_by_user_id' => $actorId,
                        'reason' => 'Vytvoření konceptu ze zdrojového sešitu depa.',
                        'before_payload' => null,
                        'after_payload' => [
                            'row_count' => (int) $batch->getAttribute(
                                'row_count',
                            ),
                            'ready_row_count' => (int) $batch->getAttribute(
                                'ready_row_count',
                            ),
                            'no_run_row_count' => (int) $batch->getAttribute(
                                'no_run_row_count',
                            ),
                            'source_sha256' => $sourceHash,
                            'source_file_stored' => false,
                        ],
                        'protected_totals_sha256_before' => $totalsHash,
                        'protected_totals_sha256_after' => $totalsHash,
                    ]);

                    return $batch->fresh() ?? throw new LogicException(
                        'The created depot-import draft could not be reloaded.',
                    );
                },
                3,
            );
        } catch (QueryException $exception) {
            if (
                str_contains(
                    $exception->getMessage(),
                    'depot_import_batches_source_alias_unique',
                )
            ) {
                throw ValidationException::withMessages([
                    'workbook' => [
                        'Stejný sešit a alias už jsou evidovány v jiné importní dávce.',
                    ],
                ]);
            }

            throw $exception;
        }
    }

    /** @param  array<string, mixed>  $input */
    public function mapSourceDriver(
        User $actor,
        string $batchPublicId,
        array $input,
    ): DepotImportBatch {
        $sourceDriverName = $this->requiredString(
            $input,
            'source_driver_name',
        );
        $sourceDriverKey = $this->aliases->normalize(
            $sourceDriverName,
        );

        if ($sourceDriverKey === '') {
            throw ValidationException::withMessages([
                'source_driver_name' => [
                    'Zdrojové jméno řidiče nesmí být prázdné.',
                ],
            ]);
        }

        return $this->mapDriverName(
            actor: $actor,
            batchPublicId: $batchPublicId,
            sourceDriverKey: $sourceDriverKey,
            driverId: (int) $input['driver_id'],
            expectedLockVersion: (int) $input['expected_lock_version'],
            reason: $this->requiredString($input, 'reason'),
        );
    }

    /** @param  array<string, mixed>  $input */
    public function finalize(
        User $actor,
        string $batchPublicId,
        array $input,
    ): DepotImportBatch {
        $organizationId = $this->organizationContext->requireId();
        $actorId = $this->actorId($actor);
        $expectedLockVersion = (int) $input['expected_lock_version'];
        $reason = $this->requiredString($input, 'reason');

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $batchPublicId,
                $expectedLockVersion,
                $reason,
            ): DepotImportBatch {
                $batch = DepotImportBatch::query()
                    ->where('organization_id', $organizationId)
                    ->where('public_id', $batchPublicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertLockVersion($batch, $expectedLockVersion);

                if (
                    $batch->getAttribute('status')
                    !== DepotImportBatch::STATUS_READY
                ) {
                    throw ValidationException::withMessages([
                        'batch' => [
                            'Import lze dokončit až po přiřazení všech připravených řádků.',
                        ],
                    ]);
                }

                /** @var EloquentCollection<int, DepotImportRow> $rows */
                $rows = $batch->rows()
                    ->orderBy('source_row')
                    ->lockForUpdate()
                    ->get();
                $this->integrity->assertBatchIntegrity($batch, $rows);

                $unassignedReady = $rows
                    ->where('status', DepotImportRow::STATUS_READY)
                    ->whereNull('assigned_driver_id')
                    ->count();

                if (
                    $unassignedReady !== 0
                    || (int) $batch->getAttribute(
                        'unassigned_ready_row_count',
                    ) !== 0
                ) {
                    throw ValidationException::withMessages([
                        'batch' => [
                            'Před dokončením importu přiřaďte všechna zdrojová jména řidičům.',
                        ],
                    ]);
                }

                $totalsHash = (string) $batch->getAttribute(
                    'protected_totals_sha256',
                );
                $previousLockVersion = (int) $batch->getAttribute(
                    'lock_version',
                );
                $batch->forceFill([
                    'status' => DepotImportBatch::STATUS_IMPORTED,
                    'lock_version' => $previousLockVersion + 1,
                ])->save();

                $this->integrity->assertBatchIntegrity($batch, $rows);

                DepotImportEvent::query()->create([
                    'depot_import_batch_id' => $batch->getKey(),
                    'depot_import_row_id' => null,
                    'organization_id' => $organizationId,
                    'event_type' => DepotImportEvent::TYPE_IMPORT_FINALIZED,
                    'acted_by_user_id' => $actorId,
                    'reason' => $reason,
                    'before_payload' => [
                        'status' => DepotImportBatch::STATUS_READY,
                        'lock_version' => $previousLockVersion,
                    ],
                    'after_payload' => [
                        'status' => DepotImportBatch::STATUS_IMPORTED,
                        'lock_version' => $previousLockVersion + 1,
                        'source_row_count' => $rows->count(),
                        'ready_row_count' => (int) $batch->getAttribute(
                            'ready_row_count',
                        ),
                        'no_run_row_count' => (int) $batch->getAttribute(
                            'no_run_row_count',
                        ),
                        'daily_reports_created' => 0,
                        'route_allocations_created' => 0,
                        'source_values_changed' => false,
                    ],
                    'protected_totals_sha256_before' => $totalsHash,
                    'protected_totals_sha256_after' => $totalsHash,
                ]);

                return $batch->fresh() ?? throw new LogicException(
                    'The finalized depot import could not be reloaded.',
                );
            },
            3,
        );
    }

    /** @param  array<string, mixed>  $input */
    public function cancel(
        User $actor,
        string $batchPublicId,
        array $input,
    ): DepotImportBatch {
        $organizationId = $this->organizationContext->requireId();
        $actorId = $this->actorId($actor);
        $expectedLockVersion = (int) $input['expected_lock_version'];
        $reason = $this->requiredString($input, 'reason');

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $batchPublicId,
                $expectedLockVersion,
                $reason,
            ): DepotImportBatch {
                $batch = DepotImportBatch::query()
                    ->where('organization_id', $organizationId)
                    ->where('public_id', $batchPublicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertLockVersion($batch, $expectedLockVersion);

                if (
                    $batch->getAttribute('status')
                    !== DepotImportBatch::STATUS_IMPORTED
                ) {
                    throw ValidationException::withMessages([
                        'batch' => [
                            'Stornovat lze pouze dokončený import zápisu depa.',
                        ],
                    ]);
                }

                /** @var EloquentCollection<int, DepotImportRow> $rows */
                $rows = $batch->rows()
                    ->orderBy('source_row')
                    ->lockForUpdate()
                    ->get();
                $this->integrity->assertBatchIntegrity($batch, $rows);

                $totalsHash = (string) $batch->getAttribute(
                    'protected_totals_sha256',
                );
                $previousLockVersion = (int) $batch->getAttribute(
                    'lock_version',
                );
                $batch->forceFill([
                    'status' => DepotImportBatch::STATUS_CANCELLED,
                    'lock_version' => $previousLockVersion + 1,
                ])->save();

                $this->integrity->assertBatchIntegrity($batch, $rows);

                DepotImportEvent::query()->create([
                    'depot_import_batch_id' => $batch->getKey(),
                    'depot_import_row_id' => null,
                    'organization_id' => $organizationId,
                    'event_type' => DepotImportEvent::TYPE_IMPORT_CANCELLED,
                    'acted_by_user_id' => $actorId,
                    'reason' => $reason,
                    'before_payload' => [
                        'status' => DepotImportBatch::STATUS_IMPORTED,
                        'lock_version' => $previousLockVersion,
                    ],
                    'after_payload' => [
                        'status' => DepotImportBatch::STATUS_CANCELLED,
                        'lock_version' => $previousLockVersion + 1,
                        'source_row_count' => $rows->count(),
                        'daily_reports_deleted' => 0,
                        'route_allocations_deleted' => 0,
                        'source_values_changed' => false,
                        'excluded_from_future_reconciliation' => true,
                    ],
                    'protected_totals_sha256_before' => $totalsHash,
                    'protected_totals_sha256_after' => $totalsHash,
                ]);

                return $batch->fresh() ?? throw new LogicException(
                    'The cancelled depot import could not be reloaded.',
                );
            },
            3,
        );
    }

    public function find(string $publicId): DepotImportBatch
    {
        $batch = DepotImportBatch::query()
            ->where(
                'organization_id',
                $this->organizationContext->requireId(),
            )
            ->where('public_id', $publicId)
            ->firstOrFail();

        /** @var EloquentCollection<int, DepotImportRow> $rows */
        $rows = $batch->rows()
            ->orderBy('source_row')
            ->get();

        $this->integrity->assertBatchIntegrity($batch, $rows);

        return $batch;
    }

    /** @return list<array<string, mixed>> */
    public function summaries(): array
    {
        return DepotImportBatch::query()
            ->where(
                'organization_id',
                $this->organizationContext->requireId(),
            )
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (DepotImportBatch $batch): array => [
                'public_id' => (string) $batch->getAttribute('public_id'),
                'status' => (string) $batch->getAttribute('status'),
                'lock_version' => (int) $batch->getAttribute('lock_version'),
                'source' => [
                    'original_filename' => (string) $batch->getAttribute(
                        'original_filename',
                    ),
                    'stored' => false,
                ],
                'confirmed_alias' => (string) $batch->getAttribute(
                    'confirmed_carrier_alias',
                ),
                'period' => [
                    'from' => $this->dateAttribute($batch, 'period_from'),
                    'until' => $this->dateAttribute($batch, 'period_until'),
                ],
                'counts' => [
                    'rows' => (int) $batch->getAttribute('row_count'),
                    'ready' => (int) $batch->getAttribute('ready_row_count'),
                    'no_run' => (int) $batch->getAttribute(
                        'no_run_row_count',
                    ),
                    'unassigned_ready' => (int) $batch->getAttribute(
                        'unassigned_ready_row_count',
                    ),
                ],
                'integrity_verified_on_open' => true,
                'finalization_enabled' => $batch->getAttribute('status')
                    === DepotImportBatch::STATUS_READY,
                'cancellation_enabled' => $batch->getAttribute('status')
                    === DepotImportBatch::STATUS_IMPORTED,
                'source_records_locked' => in_array(
                    $batch->getAttribute('status'),
                    [
                        DepotImportBatch::STATUS_IMPORTED,
                        DepotImportBatch::STATUS_CANCELLED,
                    ],
                    true,
                ),
                'created_at' => $this->dateTimeString(
                    $batch->getAttribute('created_at'),
                ),
                'updated_at' => $this->dateTimeString(
                    $batch->getAttribute('updated_at'),
                ),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function payload(DepotImportBatch $batch): array
    {
        /** @var EloquentCollection<int, DepotImportRow> $allRows */
        $allRows = $batch->rows()
            ->with('assignedDriver')
            ->orderBy('source_row')
            ->get();
        $this->integrity->assertBatchIntegrity($batch, $allRows);
        $rows = $allRows->take(self::MAX_RETURNED_ROWS);
        $events = $batch->events()
            ->reorder()
            ->latest('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();
        $cancellationEvent = $batch->events()
            ->where('event_type', DepotImportEvent::TYPE_IMPORT_CANCELLED)
            ->latest('id')
            ->first();

        return [
            'public_id' => (string) $batch->getAttribute('public_id'),
            'status' => (string) $batch->getAttribute('status'),
            'lock_version' => (int) $batch->getAttribute('lock_version'),
            'organization_id' => (int) $batch->getAttribute(
                'organization_id',
            ),
            'source' => [
                'original_filename' => (string) $batch->getAttribute(
                    'original_filename',
                ),
                'file_sha256' => (string) $batch->getAttribute(
                    'source_sha256',
                ),
                'stored' => false,
                'read_only' => true,
                'formula_values_used_for_import' => false,
            ],
            'detected' => [
                'schema_fingerprint' => (string) $batch->getAttribute(
                    'schema_fingerprint',
                ),
                'sheet_name' => (string) $batch->getAttribute('sheet_name'),
                'header_start_row' => (int) $batch->getAttribute(
                    'header_start_row',
                ),
                'header_end_row' => (int) $batch->getAttribute(
                    'header_end_row',
                ),
                'data_start_row' => (int) $batch->getAttribute(
                    'data_start_row',
                ),
            ],
            'confirmed_alias' => (string) $batch->getAttribute(
                'confirmed_carrier_alias',
            ),
            'normalized_confirmed_alias' => (string) $batch->getAttribute(
                'confirmed_carrier_alias_normalized',
            ),
            'period' => [
                'from' => $this->dateAttribute($batch, 'period_from'),
                'until' => $this->dateAttribute($batch, 'period_until'),
            ],
            'counts' => [
                'rows' => (int) $batch->getAttribute('row_count'),
                'ready' => (int) $batch->getAttribute('ready_row_count'),
                'no_run' => (int) $batch->getAttribute('no_run_row_count'),
                'excluded_carrier' => (int) $batch->getAttribute(
                    'excluded_carrier_row_count',
                ),
                'source_drivers' => (int) $batch->getAttribute(
                    'source_driver_count',
                ),
                'unassigned_ready' => (int) $batch->getAttribute(
                    'unassigned_ready_row_count',
                ),
            ],
            'source_totals' => $batch->getAttribute('source_totals'),
            'protected_totals_sha256' => (string) $batch->getAttribute(
                'protected_totals_sha256',
            ),
            'integrity_verified' => true,
            'eligible_drivers' => $this->eligibleDrivers($batch),
            'rows' => $rows
                ->map(fn (DepotImportRow $row): array => $this->rowPayload($row))
                ->values()
                ->all(),
            'rows_truncated' => $allRows->count() > $rows->count(),
            'events' => $events
                ->map(fn (DepotImportEvent $event): array => [
                    'id' => (int) $event->getKey(),
                    'type' => (string) $event->getAttribute('event_type'),
                    'row_public_id' => $event->row()
                        ->value('public_id'),
                    'acted_by_user_id' => (int) $event->getAttribute(
                        'acted_by_user_id',
                    ),
                    'reason' => $event->getAttribute('reason'),
                    'before' => $event->getAttribute('before_payload'),
                    'after' => $event->getAttribute('after_payload'),
                    'protected_totals_sha256_before' => (string) $event
                        ->getAttribute('protected_totals_sha256_before'),
                    'protected_totals_sha256_after' => (string) $event
                        ->getAttribute('protected_totals_sha256_after'),
                    'created_at' => $this->dateTimeString(
                        $event->getAttribute('created_at'),
                    ),
                ])
                ->all(),
            'daily_reports_created' => 0,
            'route_allocations_created' => 0,
            'finalization_enabled' => $batch->getAttribute('status')
                === DepotImportBatch::STATUS_READY,
            'cancellation_enabled' => $batch->getAttribute('status')
                === DepotImportBatch::STATUS_IMPORTED,
            'cancellation' => $cancellationEvent instanceof DepotImportEvent
                ? [
                    'reason' => (string) $cancellationEvent->getAttribute(
                        'reason',
                    ),
                    'acted_by_user_id' => (int) $cancellationEvent
                        ->getAttribute('acted_by_user_id'),
                    'created_at' => $this->dateTimeString(
                        $cancellationEvent->getAttribute('created_at'),
                    ),
                ]
                : null,
            'source_records_locked' => in_array(
                $batch->getAttribute('status'),
                [
                    DepotImportBatch::STATUS_IMPORTED,
                    DepotImportBatch::STATUS_CANCELLED,
                ],
                true,
            ),
            'reconciliation_available_here' => false,
        ];
    }

    private function mapDriverName(
        User $actor,
        string $batchPublicId,
        string $sourceDriverKey,
        int $driverId,
        int $expectedLockVersion,
        string $reason,
    ): DepotImportBatch {
        $organizationId = $this->organizationContext->requireId();
        $actorId = $this->actorId($actor);

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $batchPublicId,
                $sourceDriverKey,
                $driverId,
                $expectedLockVersion,
                $reason,
            ): DepotImportBatch {
                $batch = DepotImportBatch::query()
                    ->where('organization_id', $organizationId)
                    ->where('public_id', $batchPublicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertEditable($batch);
                $this->assertLockVersion(
                    $batch,
                    $expectedLockVersion,
                );

                /** @var EloquentCollection<int, DepotImportRow> $allRows */
                $allRows = $batch->rows()
                    ->orderBy('source_row')
                    ->lockForUpdate()
                    ->get();
                $this->integrity->assertBatchIntegrity(
                    $batch,
                    $allRows,
                );
                $totalsHash = (string) $batch->getAttribute(
                    'protected_totals_sha256',
                );

                $rows = $allRows
                    ->filter(
                        static fn (DepotImportRow $row): bool => $row->getAttribute('source_driver_key')
                            === $sourceDriverKey,
                    )
                    ->values();

                if ($rows->isEmpty()) {
                    throw ValidationException::withMessages([
                        'source_driver_name' => [
                            'Zdrojové jméno řidiče v dávce nebylo nalezeno.',
                        ],
                    ]);
                }

                $beforeAssignments = [];
                $changedRows = [];

                foreach ($rows as $row) {
                    $assignment = $this->eligibleAssignment(
                        organizationId: $organizationId,
                        driverId: $driverId,
                        serviceDate: $this->dateAttribute(
                            $row,
                            'service_date',
                        ),
                    );
                    $previousDriverId = $row->getAttribute(
                        'assigned_driver_id',
                    );
                    $previousAssignmentId = $row->getAttribute(
                        'assigned_driver_organization_assignment_id',
                    );
                    $beforeAssignments[] = [
                        'source_row' => (int) $row->getAttribute('source_row'),
                        'driver_id' => $previousDriverId === null
                            ? null
                            : (int) $previousDriverId,
                        'assignment_id' => $previousAssignmentId === null
                            ? null
                            : (int) $previousAssignmentId,
                    ];

                    if (
                        (int) $previousDriverId === $driverId
                        && (int) $previousAssignmentId
                            === (int) $assignment->getKey()
                    ) {
                        continue;
                    }

                    $row->forceFill([
                        'assigned_driver_id' => $driverId,
                        'assigned_driver_organization_assignment_id' => (int) $assignment->getKey(),
                    ])->save();
                    $changedRows[] = $row;
                }

                if ($changedRows === []) {
                    return $batch;
                }

                /** @var EloquentCollection<int, DepotImportRow> $afterRows */
                $afterRows = $batch->rows()
                    ->orderBy('source_row')
                    ->get();
                $this->integrity->assertBatchIntegrity(
                    $batch,
                    $afterRows,
                );
                $unassignedReady = $afterRows
                    ->where('status', DepotImportRow::STATUS_READY)
                    ->whereNull('assigned_driver_id')
                    ->count();
                $batch->forceFill([
                    'unassigned_ready_row_count' => $unassignedReady,
                    'status' => $unassignedReady === 0
                        ? DepotImportBatch::STATUS_READY
                        : DepotImportBatch::STATUS_DRAFT,
                    'lock_version' => (int) $batch->getAttribute(
                        'lock_version',
                    ) + 1,
                ])->save();

                $this->integrity->assertBatchIntegrity(
                    $batch,
                    $afterRows,
                );

                DepotImportEvent::query()->create([
                    'depot_import_batch_id' => $batch->getKey(),
                    'depot_import_row_id' => null,
                    'organization_id' => $organizationId,
                    'event_type' => DepotImportEvent::TYPE_SOURCE_DRIVER_MAPPED,
                    'acted_by_user_id' => $actorId,
                    'reason' => $reason,
                    'before_payload' => [
                        'source_driver_key' => $sourceDriverKey,
                        'assignments' => $beforeAssignments,
                    ],
                    'after_payload' => [
                        'driver_id' => $driverId,
                        'affected_source_rows' => array_map(
                            static fn (DepotImportRow $row): int => (int) $row->getAttribute('source_row'),
                            $changedRows,
                        ),
                        'unassigned_ready_row_count' => $unassignedReady,
                        'batch_status' => (string) $batch->getAttribute(
                            'status',
                        ),
                        'lock_version' => (int) $batch->getAttribute(
                            'lock_version',
                        ),
                    ],
                    'protected_totals_sha256_before' => $totalsHash,
                    'protected_totals_sha256_after' => $totalsHash,
                ]);

                return $batch->fresh() ?? throw new LogicException(
                    'The updated depot-import draft could not be reloaded.',
                );
            },
            3,
        );
    }

    private function eligibleAssignment(
        int $organizationId,
        int $driverId,
        string $serviceDate,
    ): DriverOrganizationAssignment {
        $assignment = DriverOrganizationAssignment::query()
            ->where('organization_id', $organizationId)
            ->where('driver_id', $driverId)
            ->whereDate('valid_from', '<=', $serviceDate)
            ->where(function ($query) use ($serviceDate): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $serviceDate);
            })
            ->whereHas('driver', function ($query): void {
                $query
                    ->where('status', Driver::STATUS_ACTIVE)
                    ->where('active', true);
            })
            ->orderByDesc('valid_from')
            ->first();

        if (! $assignment instanceof DriverOrganizationAssignment) {
            throw ValidationException::withMessages([
                'driver_id' => [
                    sprintf(
                        'Řidič není aktivní a oprávněný pro hlavního dopravce k datu %s.',
                        CarbonImmutable::parse($serviceDate)->format('d.m.Y'),
                    ),
                ],
            ]);
        }

        return $assignment;
    }

    private function assertEditable(DepotImportBatch $batch): void
    {
        if (
            ! in_array(
                $batch->getAttribute('status'),
                [
                    DepotImportBatch::STATUS_DRAFT,
                    DepotImportBatch::STATUS_READY,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'batch' => [
                    'Řidiče lze měnit pouze v nedokončeném konceptu importu.',
                ],
            ]);
        }
    }

    private function assertLockVersion(
        DepotImportBatch $batch,
        int $expected,
    ): void {
        if ((int) $batch->getAttribute('lock_version') !== $expected) {
            throw ValidationException::withMessages([
                'expected_lock_version' => [
                    'Importní dávku mezitím změnil jiný uživatel. Obnovte její aktuální stav.',
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function rowAttributes(int $batchId, array $row): array
    {
        $status = $this->requiredString($row, 'status');

        if (
            ! in_array(
                $status,
                DepotImportRow::STATUSES,
                true,
            )
        ) {
            throw new LogicException(
                'Only validated ready and no-run rows can be persisted.',
            );
        }

        $route = $this->routeNumbers->normalize(
            $this->requiredString($row, 'route_number'),
        );
        $sourceDriverName = $this->requiredString(
            $row,
            'source_driver_name',
        );
        $sourceDriverKey = $this->aliases->normalize(
            $sourceDriverName,
        );

        if ($sourceDriverKey === '') {
            throw new LogicException(
                'The source driver key cannot be empty.',
            );
        }

        return [
            'depot_import_batch_id' => $batchId,
            'source_row' => $this->positiveInteger($row, 'source_row'),
            'status' => $status,
            'service_date' => $this->requiredString($row, 'service_date'),
            'route_number' => $route['route_number'],
            'route_number_normalized' => $route[
                'route_number_normalized'
            ],
            'carrier_name' => $this->requiredString($row, 'carrier_name'),
            'source_driver_name' => $sourceDriverName,
            'source_driver_key' => $sourceDriverKey,
            'assigned_driver_id' => null,
            'assigned_driver_organization_assignment_id' => null,
            'departure_time' => $row['departure_time'] ?? null,
            'arrival_time' => $row['arrival_time'] ?? null,
            'actual_km' => $row['actual_km'] ?? null,
            'planned_km' => $row['planned_km'] ?? null,
            'loaded_parcels' => $row['loaded_parcels'] ?? null,
            'delivered_parcels' => $row['delivered_parcels'] ?? null,
            'redirected_parcels' => $row['redirected_parcels'] ?? null,
            'customer_rejected_parcels' => $row[
                'customer_rejected_parcels'
            ] ?? null,
            'reported_not_delivered_parcels' => $row[
                'reported_not_delivered_parcels'
            ] ?? null,
            'computed_not_delivered_parcels' => $row[
                'computed_not_delivered_parcels'
            ] ?? null,
            'surcharge_amount' => $row['surcharge_amount'] ?? null,
            'operational_notes' => $row['operational_notes'] ?? null,
            'errors' => is_array($row['errors'] ?? null)
                ? $row['errors']
                : [],
            'warnings' => is_array($row['warnings'] ?? null)
                ? $row['warnings']
                : [],
        ];
    }

    /** @return array<string, mixed> */
    private function rowPayload(DepotImportRow $row): array
    {
        $driver = $row->assignedDriver;

        return [
            'public_id' => (string) $row->getAttribute('public_id'),
            'source_row' => (int) $row->getAttribute('source_row'),
            'status' => (string) $row->getAttribute('status'),
            'service_date' => $this->dateAttribute($row, 'service_date'),
            'service_date_display' => CarbonImmutable::parse(
                $this->dateAttribute($row, 'service_date'),
            )->format('d.m.Y'),
            'route_number' => (string) $row->getAttribute('route_number'),
            'carrier_name' => (string) $row->getAttribute('carrier_name'),
            'source_driver_name' => (string) $row->getAttribute(
                'source_driver_name',
            ),
            'assigned_driver' => $driver instanceof Driver
                ? [
                    'id' => (int) $driver->getKey(),
                    'name' => $driver->full_name,
                    'assignment_id' => (int) $row->getAttribute(
                        'assigned_driver_organization_assignment_id',
                    ),
                ]
                : null,
            'values' => [
                'departure_time' => $row->getAttribute('departure_time'),
                'arrival_time' => $row->getAttribute('arrival_time'),
                'actual_km' => $row->getAttribute('actual_km'),
                'planned_km' => $row->getAttribute('planned_km'),
                'loaded_parcels' => $row->getAttribute('loaded_parcels'),
                'delivered_parcels' => $row->getAttribute(
                    'delivered_parcels',
                ),
                'redirected_parcels' => $row->getAttribute(
                    'redirected_parcels',
                ),
                'customer_rejected_parcels' => $row->getAttribute(
                    'customer_rejected_parcels',
                ),
                'reported_not_delivered_parcels' => $row->getAttribute(
                    'reported_not_delivered_parcels',
                ),
                'computed_not_delivered_parcels' => $row->getAttribute(
                    'computed_not_delivered_parcels',
                ),
                'surcharge_amount' => $row->getAttribute(
                    'surcharge_amount',
                ),
                'operational_notes' => $row->getAttribute(
                    'operational_notes',
                ),
            ],
            'errors' => $row->getAttribute('errors'),
            'warnings' => $row->getAttribute('warnings'),
            'protected_values_sha256' => (string) $row->getAttribute(
                'protected_values_sha256',
            ),
        ];
    }

    /** @return list<array<string, int|string|null>> */
    private function eligibleDrivers(DepotImportBatch $batch): array
    {
        $from = $this->dateAttribute($batch, 'period_from');
        $until = $this->dateAttribute($batch, 'period_until');

        return DriverOrganizationAssignment::query()
            ->where(
                'organization_id',
                (int) $batch->getAttribute('organization_id'),
            )
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
            ->get()
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

    private function actorId(User $actor): int
    {
        $identifier = (int) $actor->getKey();

        if ($identifier < 1) {
            throw new LogicException(
                'The depot-import actor must be persisted.',
            );
        }

        return $identifier;
    }

    /** @param  array<string, mixed>  $input */
    private function requiredString(array $input, string $key): string
    {
        $value = $input[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new LogicException(
                sprintf('Required depot-import value [%s] is unavailable.', $key),
            );
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function arrayValue(array $input, string $key): array
    {
        $value = $input[$key] ?? null;

        if (! is_array($value)) {
            throw new LogicException(
                sprintf('Required depot-import array [%s] is unavailable.', $key),
            );
        }

        return $value;
    }

    /** @param  array<string, mixed>  $input */
    private function positiveInteger(array $input, string $key): int
    {
        $value = $input[$key] ?? null;

        if (! is_int($value) || $value < 1) {
            throw new LogicException(
                sprintf('Required depot-import integer [%s] is invalid.', $key),
            );
        }

        return $value;
    }

    /** @param  array<string, mixed>  $input */
    private function requiredHash(array $input, string $key): string
    {
        $value = $this->requiredString($input, $key);

        if (preg_match('/^[0-9A-F]{64}$/', $value) !== 1) {
            throw new LogicException(
                sprintf('Required depot-import hash [%s] is invalid.', $key),
            );
        }

        return $value;
    }

    private function filename(string $filename): string
    {
        $normalized = basename(
            str_replace('\\', '/', trim($filename)),
        );

        if ($normalized === '' || $normalized === '.' || $normalized === '..') {
            return 'depot-import.xlsx';
        }

        return mb_substr($normalized, 0, 255, 'UTF-8');
    }

    private function dateAttribute(
        DepotImportBatch|DepotImportRow $model,
        string $field,
    ): string {
        $value = $model->getAttribute($field);

        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : (string) $value;
    }

    private function dateTimeString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
