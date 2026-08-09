<?php

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

final class DailyReportPersistenceService
{
    /** @var list<string> */
    private const ALLOWED_DRAFT_ATTRIBUTES = [
        'daily_report_form_configuration_id',
        'custom_field_values',
        'completion_confirmed_at',
        'departure_time',
        'arrival_time',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'surcharge_amount',
        'operational_notes',
    ];

    /** @var list<string> */
    private const ALLOWED_DRAFT_UPDATE_ATTRIBUTES = [
        'route_number',
        'service_date',
        'completion_confirmed_at',
        'departure_time',
        'arrival_time',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'surcharge_amount',
        'operational_notes',
    ];

    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly RouteNumberNormalizer $routeNumberNormalizer,
        private readonly DailyReportSnapshotBuilder $snapshotBuilder,
        private readonly DailyReportEventPayloadBuilder $eventPayloadBuilder,
        private readonly DailyReportWorkflow $workflow,
        private readonly DailyReportEffectiveFormService $effectiveForm,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createDraft(
        int $performedByDriverId,
        int $enteredByUserId,
        string $routeNumber,
        DateTimeInterface|string $serviceDate,
        array $attributes = [],
        ?string $reason = null,
    ): DailyReport {
        return $this->createDraftForActor(
            performedByDriverId: $performedByDriverId,
            enteredByUserId: $enteredByUserId,
            routeNumber: $routeNumber,
            serviceDate: $serviceDate,
            attributes: $attributes,
            reason: $reason,
            enteredOnBehalf: false,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createDelegatedDraft(
        int $performedByDriverId,
        int $enteredByUserId,
        string $routeNumber,
        DateTimeInterface|string $serviceDate,
        array $attributes = [],
        ?string $reason = null,
    ): DailyReport {
        return $this->createDraftForActor(
            performedByDriverId: $performedByDriverId,
            enteredByUserId: $enteredByUserId,
            routeNumber: $routeNumber,
            serviceDate: $serviceDate,
            attributes: $attributes,
            reason: $reason,
            enteredOnBehalf: true,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAuthorizedImportDraft(
        int $performedByDriverId,
        int $importedByUserId,
        string $routeNumber,
        DateTimeInterface|string $serviceDate,
        array $attributes = [],
        ?string $reason = null,
    ): DailyReport {
        return $this->createDraftForActor(
            performedByDriverId: $performedByDriverId,
            enteredByUserId: $importedByUserId,
            routeNumber: $routeNumber,
            serviceDate: $serviceDate,
            attributes: $attributes,
            reason: $reason,
            enteredOnBehalf: false,
            entryMethod: DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createDraftForActor(
        int $performedByDriverId,
        int $enteredByUserId,
        string $routeNumber,
        DateTimeInterface|string $serviceDate,
        array $attributes,
        ?string $reason,
        bool $enteredOnBehalf,
        ?string $entryMethod = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $entryMethod ??= $enteredOnBehalf
            ? DailyReport::ENTRY_METHOD_DELEGATED
            : DailyReport::ENTRY_METHOD_DRIVER;

        $this->assertPositiveIdentifier(
            $performedByDriverId,
            'Performed-by driver identifier',
        );

        $this->assertPositiveIdentifier(
            $enteredByUserId,
            'Entering user identifier',
        );

        $this->assertAllowedAttributes($attributes);

        $normalizedRoute = $this->routeNumberNormalizer->normalize(
            $routeNumber,
        );

        if (
            mb_strlen(
                $normalizedRoute['route_number'],
                'UTF-8',
            ) > 100
        ) {
            throw new InvalidArgumentException(
                'Route number must not exceed 100 characters.',
            );
        }

        $normalizedServiceDate = $this->normalizeServiceDate(
            $serviceDate,
        );

        $formConfigurationId =
            $attributes['daily_report_form_configuration_id']
                ?? null;

        if ($formConfigurationId !== null) {
            if (
                ! is_int($formConfigurationId)
                && ! (
                    is_string($formConfigurationId)
                    && ctype_digit($formConfigurationId)
                )
            ) {
                throw new InvalidArgumentException(
                    'Daily-report form configuration identifier must be a positive integer or null.',
                );
            }

            $formConfigurationId = (int) $formConfigurationId;

            $this->assertPositiveIdentifier(
                $formConfigurationId,
                'Daily-report form configuration identifier',
            );
        }

        $customFieldValues =
            $attributes['custom_field_values']
                ?? [];

        if (! is_array($customFieldValues)) {
            throw new InvalidArgumentException(
                'Custom daily-report field values must be an array.',
            );
        }

        $completionConfirmedAt = $this->normalizeNullableDateTime(
            $attributes['completion_confirmed_at'] ?? null,
            'Completion confirmation',
        );

        $departureTime = $this->normalizeNullableTime(
            $attributes['departure_time'] ?? null,
            'Departure time',
        );

        $arrivalTime = $this->normalizeNullableTime(
            $attributes['arrival_time'] ?? null,
            'Arrival time',
        );

        $loadedParcels = $this->normalizeNullableParcelCount(
            $attributes['loaded_parcels'] ?? null,
            'Loaded parcels',
        );

        $deliveredParcels = $this->normalizeNullableParcelCount(
            $attributes['delivered_parcels'] ?? null,
            'Delivered parcels',
        );

        $redirectedParcels = $this->normalizeNullableParcelCount(
            $attributes['redirected_parcels'] ?? null,
            'Redirected parcels',
        );

        $undeliveredParcels = $this->normalizeNullableParcelCount(
            $attributes['undelivered_parcels'] ?? null,
            'Undelivered parcels',
        );

        $plannedKm = $this->normalizeNullableKilometres(
            $attributes['planned_km'] ?? null,
            'Planned kilometres',
        );

        $actualKm = $this->normalizeNullableKilometres(
            $attributes['actual_km'] ?? null,
            'Actual kilometres',
        );

        $actualKmSource = $this->normalizeActualKmSource(
            $attributes['actual_km_source'] ?? null,
        );

        if (
            ($actualKm === null) !==
            ($actualKmSource === null)
        ) {
            throw new InvalidArgumentException(
                'Actual kilometres and their source must be provided together.',
            );
        }

        $surchargeAmount = $this->normalizeSurchargeAmount(
            $attributes['surcharge_amount'] ?? null,
        );

        $operationalNotes = $this->normalizeNullableText(
            $attributes['operational_notes'] ?? null,
            'Operational notes',
        );

        $this->assertSurchargeNoteConsistency(
            $surchargeAmount,
            $operationalNotes,
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Change reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $performedByDriverId,
                $enteredByUserId,
                $normalizedRoute,
                $normalizedServiceDate,
                $formConfigurationId,
                $customFieldValues,
                $completionConfirmedAt,
                $departureTime,
                $arrivalTime,
                $loadedParcels,
                $deliveredParcels,
                $redirectedParcels,
                $undeliveredParcels,
                $plannedKm,
                $actualKm,
                $actualKmSource,
                $surchargeAmount,
                $operationalNotes,
                $normalizedReason,
                $entryMethod,
                $enteredOnBehalf,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $driver = Driver::query()
                    ->whereKey($performedByDriverId)
                    ->first();

                if (
                    $driver === null ||
                    ! $driver->canOperate()
                ) {
                    throw new DomainException(
                        'The performed-by driver is not active.',
                    );
                }

                $driverUserId = (int) $driver->getAttribute('user_id');

                $this->assertActiveUserMembership(
                    $enteredByUserId,
                    $organizationId,
                );

                if (
                    $entryMethod ===
                    DailyReport::ENTRY_METHOD_DRIVER
                ) {
                    if (
                        $enteredOnBehalf ||
                        $driverUserId !== $enteredByUserId
                    ) {
                        throw new DomainException(
                            'Direct draft entry must use the driver user account.',
                        );
                    }
                } elseif (
                    $entryMethod ===
                    DailyReport::ENTRY_METHOD_DELEGATED
                ) {
                    if (
                        ! $enteredOnBehalf ||
                        $driverUserId === $enteredByUserId
                    ) {
                        throw new DomainException(
                            'Delegated draft entry must use a user other than the driver user account.',
                        );
                    }

                    $this->assertOrganizationPermission(
                        $enteredByUserId,
                        $organizationId,
                        'daily-reports.enter-for-driver',
                    );
                } elseif (
                    $entryMethod ===
                    DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT
                ) {
                    if ($enteredOnBehalf) {
                        throw new LogicException(
                            'Authorized import cannot be marked as delegated entry.',
                        );
                    }

                    $this->assertOrganizationPermission(
                        $enteredByUserId,
                        $organizationId,
                        'daily-reports.enter-for-driver',
                    );
                } else {
                    throw new LogicException(
                        'Unsupported daily report entry method.',
                    );
                }

                $dailyReport = DailyReport::query()->create([
                    'organization_id' => $organizationId,
                    'trip_id' => null,
                    'performed_by_driver_id' => $performedByDriverId,
                    'vehicle_id' => null,
                    'entered_by_user_id' => $enteredByUserId,
                    'route_number' => $normalizedRoute['route_number'],
                    'route_number_normalized' => $normalizedRoute[
                            'route_number_normalized'
                        ],
                    'service_date' => $normalizedServiceDate,
                    'status' => DailyReport::STATUS_DRAFT,
                    'entry_method' => $entryMethod,
                    'entered_on_behalf' => $enteredOnBehalf,
                    'daily_report_form_configuration_id' => $formConfigurationId,
                    'custom_field_values' => $customFieldValues,
                    'completion_confirmed_at' => $completionConfirmedAt,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'loaded_parcels' => $loadedParcels,
                    'delivered_parcels' => $deliveredParcels,
                    'redirected_parcels' => $redirectedParcels,
                    'undelivered_parcels' => $undeliveredParcels,
                    'planned_km' => $plannedKm,
                    'actual_km' => $actualKm,
                    'actual_km_source' => $actualKmSource,
                    'surcharge_amount' => $surchargeAmount,
                    'operational_notes' => $operationalNotes,
                    'current_version' => 1,
                    'submitted_at' => null,
                    'review_started_at' => null,
                    'reviewed_by_user_id' => null,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'closed_at' => null,
                ]);

                $snapshotAttributes = $dailyReport->getAttributes();
                $snapshotAttributes['service_date'] = $normalizedServiceDate;

                $snapshot = $this->snapshotBuilder->build(
                    $snapshotAttributes,
                );

                DailyReportVersion::query()->create([
                    'daily_report_id' => (int) $dailyReport->getKey(),
                    'version_number' => 1,
                    'snapshot' => $snapshot,
                    'changed_fields' => DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
                    'created_by_user_id' => $enteredByUserId,
                    'change_reason' => $normalizedReason,
                ]);

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_CREATED,
                    actedByUserId: $enteredByUserId,
                    fromStatus: null,
                    toStatus: DailyReport::STATUS_DRAFT,
                    reason: $normalizedReason,
                    affectedFields: DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
                    metadata: [
                        'version_number' => 1,
                        'entry_method' => $entryMethod,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                if ($enteredOnBehalf) {
                    $delegatedEventPayload = $this->eventPayloadBuilder->build(
                        dailyReportId: (int) $dailyReport->getKey(),
                        organizationId: $organizationId,
                        eventType: DailyReportEvent::TYPE_DELEGATED_ENTRY_RECORDED,
                        actedByUserId: $enteredByUserId,
                        fromStatus: DailyReport::STATUS_DRAFT,
                        toStatus: DailyReport::STATUS_DRAFT,
                        reason: $normalizedReason,
                        affectedFields: [
                            'performed_by_driver_id',
                            'entered_by_user_id',
                            'entry_method',
                            'entered_on_behalf',
                        ],
                        metadata: [
                            'version_number' => 1,
                            'performed_by_driver_id' => $performedByDriverId,
                            'entered_by_user_id' => $enteredByUserId,
                        ],
                    );

                    DailyReportEvent::query()->create(
                        $delegatedEventPayload,
                    );
                }

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The created daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDraft(
        int $dailyReportId,
        int $enteredByUserId,
        int $expectedVersion,
        array $attributes,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $enteredByUserId,
            'Entering user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $this->assertAllowedUpdateAttributes($attributes);

        if ($attributes === []) {
            throw new InvalidArgumentException(
                'At least one daily report draft field must be provided.',
            );
        }

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Change reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $enteredByUserId,
                $expectedVersion,
                $attributes,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $enteredByUserId,
                    $organizationId,
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $dailyReport->getAttribute('status') !==
                    DailyReport::STATUS_DRAFT
                ) {
                    throw new DomainException(
                        'Only draft daily reports can be updated.',
                    );
                }

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $driver = Driver::query()
                    ->whereKey(
                        (int) $dailyReport->getAttribute(
                            'performed_by_driver_id',
                        ),
                    )
                    ->first();

                if (
                    $driver === null ||
                    ! $driver->canOperate()
                ) {
                    throw new DomainException(
                        'The performed-by driver is not active.',
                    );
                }

                $this->assertAuthorizedDraftEntryActor(
                    dailyReport: $dailyReport,
                    driver: $driver,
                    enteredByUserId: $enteredByUserId,
                    organizationId: $organizationId,
                    directFailureMessage: 'Direct draft update must use the driver user account.',
                    delegatedFailureMessage: 'Delegated draft update must use the original delegated entry actor.',
                );

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $normalizedUpdates = $this->normalizeDraftUpdateAttributes(
                    $dailyReport,
                    $attributes,
                );

                $dailyReport->fill($normalizedUpdates);

                $candidateAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $businessChangedFields = (
                    $this->snapshotBuilder->changedFields(
                        $beforeAttributes,
                        $candidateAttributes,
                    )
                );

                if ($businessChangedFields === []) {
                    throw new InvalidArgumentException(
                        'Daily report draft update does not change any persisted field.',
                    );
                }

                $newVersion = $currentVersion + 1;

                $dailyReport->setAttribute(
                    'current_version',
                    $newVersion,
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $snapshot = $this->snapshotBuilder->build(
                    $afterAttributes,
                );

                DailyReportVersion::query()->create([
                    'daily_report_id' => (int) $dailyReport->getKey(),
                    'version_number' => $newVersion,
                    'snapshot' => $snapshot,
                    'changed_fields' => $changedFields,
                    'created_by_user_id' => $enteredByUserId,
                    'change_reason' => $normalizedReason,
                ]);

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_UPDATED,
                    actedByUserId: $enteredByUserId,
                    fromStatus: DailyReport::STATUS_DRAFT,
                    toStatus: DailyReport::STATUS_DRAFT,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'previous_version' => $currentVersion,
                        'version_number' => $newVersion,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The updated daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    public function submitDraft(
        int $dailyReportId,
        int $enteredByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $enteredByUserId,
            'Entering user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Submission reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $enteredByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $enteredByUserId,
                    $organizationId,
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_DRAFT
                ) {
                    throw new DomainException(
                        'Only draft daily reports can be submitted.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_SUBMITTED,
                );

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $driver = Driver::query()
                    ->whereKey(
                        (int) $dailyReport->getAttribute(
                            'performed_by_driver_id',
                        ),
                    )
                    ->first();

                if (
                    $driver === null ||
                    ! $driver->canOperate()
                ) {
                    throw new DomainException(
                        'The performed-by driver is not active.',
                    );
                }

                $this->assertAuthorizedDraftEntryActor(
                    dailyReport: $dailyReport,
                    driver: $driver,
                    enteredByUserId: $enteredByUserId,
                    organizationId: $organizationId,
                    directFailureMessage: 'Direct draft submission must use the driver user account.',
                    delegatedFailureMessage: 'Delegated draft submission must use the original delegated entry actor.',
                    allowAuthorizedImport: true,
                );

                $this->assertCompleteForSubmission($dailyReport);

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_SUBMITTED,
                );

                $dailyReport->setAttribute(
                    'submitted_at',
                    CarbonImmutable::now(),
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_SUBMITTED,
                    actedByUserId: $enteredByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_SUBMITTED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The submitted daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    public function startReview(
        int $dailyReportId,
        int $reviewedByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $reviewedByUserId,
            'Reviewing user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Review start reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $reviewedByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $reviewedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $reviewedByUserId,
                    $organizationId,
                    'daily-reports.review',
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_SUBMITTED
                ) {
                    throw new DomainException(
                        'Only submitted daily reports can enter review.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_UNDER_REVIEW,
                );

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_UNDER_REVIEW,
                );

                $dailyReport->setAttribute(
                    'review_started_at',
                    CarbonImmutable::now(),
                );

                $dailyReport->setAttribute(
                    'reviewed_by_user_id',
                    $reviewedByUserId,
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_REVIEW_STARTED,
                    actedByUserId: $reviewedByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_UNDER_REVIEW,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                        'reviewed_by_user_id' => $reviewedByUserId,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The daily report under review could not be reloaded.',
                );
            },
            3,
        );
    }

    public function requestCorrection(
        int $dailyReportId,
        int $requestedByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $requestedByUserId,
            'Correction requesting user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Correction request reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $requestedByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $requestedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $requestedByUserId,
                    $organizationId,
                    'daily-reports.request-correction',
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_UNDER_REVIEW
                ) {
                    throw new DomainException(
                        'Only daily reports under review can have a correction requested.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_CORRECTION_REQUESTED,
                );

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_CORRECTION_REQUESTED,
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_CORRECTION_REQUESTED,
                    actedByUserId: $requestedByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_CORRECTION_REQUESTED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                        'correction_requested_by_user_id' => $requestedByUserId,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The daily report awaiting correction could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordCorrection(
        int $dailyReportId,
        int $enteredByUserId,
        int $expectedVersion,
        array $attributes,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $enteredByUserId,
            'Entering user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $this->assertAllowedUpdateAttributes($attributes);

        if ($attributes === []) {
            throw new InvalidArgumentException(
                'At least one corrected daily report field must be provided.',
            );
        }

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Correction reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $enteredByUserId,
                $expectedVersion,
                $attributes,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $enteredByUserId,
                    $organizationId,
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_CORRECTION_REQUESTED
                ) {
                    throw new DomainException(
                        'Only daily reports awaiting correction can be corrected.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_CORRECTED,
                );

                $entryMethod = $dailyReport->getAttribute(
                    'entry_method',
                );

                $enteredOnBehalf = $dailyReport->getAttribute(
                    'entered_on_behalf',
                );

                if (
                    ! is_string($entryMethod) ||
                    ! is_bool($enteredOnBehalf)
                ) {
                    throw new LogicException(
                        'Daily report entry actor state is not available.',
                    );
                }

                $isDirectDriverReport =
                    $entryMethod === DailyReport::ENTRY_METHOD_DRIVER &&
                    $enteredOnBehalf === false;

                $isDelegatedReport =
                    $entryMethod === DailyReport::ENTRY_METHOD_DELEGATED &&
                    $enteredOnBehalf === true;

                if (
                    ! $isDirectDriverReport &&
                    ! $isDelegatedReport
                ) {
                    throw new DomainException(
                        'Only driver or delegated daily reports can be corrected.',
                    );
                }

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $driver = Driver::query()
                    ->whereKey(
                        (int) $dailyReport->getAttribute(
                            'performed_by_driver_id',
                        ),
                    )
                    ->first();

                if (
                    $driver === null ||
                    ! $driver->canOperate()
                ) {
                    throw new DomainException(
                        'The performed-by driver is not active.',
                    );
                }

                $driverUserId = (int) $driver->getAttribute(
                    'user_id',
                );

                $originalEntryUserId = (int) $dailyReport->getAttribute(
                    'entered_by_user_id',
                );

                $isActualDriverCorrection =
                    $driverUserId === $enteredByUserId;

                $isOriginalDelegatedActorCorrection =
                    $isDelegatedReport &&
                    $originalEntryUserId === $enteredByUserId;

                if ($isOriginalDelegatedActorCorrection) {
                    $this->assertOrganizationPermission(
                        $enteredByUserId,
                        $organizationId,
                        'daily-reports.enter-for-driver',
                    );
                }

                if (
                    ! $isActualDriverCorrection &&
                    ! $isOriginalDelegatedActorCorrection
                ) {
                    throw new DomainException(
                        'The correcting user is not authorized for this daily report.',
                    );
                }

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $normalizedUpdates = $this->normalizeDraftUpdateAttributes(
                    $dailyReport,
                    $attributes,
                );

                $dailyReport->fill($normalizedUpdates);

                $candidateAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $businessChangedFields = (
                    $this->snapshotBuilder->changedFields(
                        $beforeAttributes,
                        $candidateAttributes,
                    )
                );

                if ($businessChangedFields === []) {
                    throw new InvalidArgumentException(
                        'Daily report correction does not change any persisted field.',
                    );
                }

                $newVersion = $currentVersion + 1;

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_CORRECTED,
                );

                $dailyReport->setAttribute(
                    'current_version',
                    $newVersion,
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $snapshot = $this->snapshotBuilder->build(
                    $afterAttributes,
                );

                DailyReportVersion::query()->create([
                    'daily_report_id' => (int) $dailyReport->getKey(),
                    'version_number' => $newVersion,
                    'snapshot' => $snapshot,
                    'changed_fields' => $changedFields,
                    'created_by_user_id' => $enteredByUserId,
                    'change_reason' => $normalizedReason,
                ]);

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_CORRECTED,
                    actedByUserId: $enteredByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_CORRECTED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'previous_version' => $currentVersion,
                        'version_number' => $newVersion,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The corrected daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    public function resubmitCorrected(
        int $dailyReportId,
        int $enteredByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $enteredByUserId,
            'Entering user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Resubmission reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $enteredByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $enteredByUserId,
                    $organizationId,
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_CORRECTED
                ) {
                    throw new DomainException(
                        'Only corrected daily reports can be resubmitted.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_SUBMITTED,
                );

                $entryMethod = $dailyReport->getAttribute(
                    'entry_method',
                );

                $enteredOnBehalf = $dailyReport->getAttribute(
                    'entered_on_behalf',
                );

                if (
                    ! is_string($entryMethod) ||
                    ! is_bool($enteredOnBehalf)
                ) {
                    throw new LogicException(
                        'Daily report entry actor state is not available.',
                    );
                }

                $isDirectDriverReport =
                    $entryMethod === DailyReport::ENTRY_METHOD_DRIVER &&
                    $enteredOnBehalf === false;

                $isDelegatedReport =
                    $entryMethod === DailyReport::ENTRY_METHOD_DELEGATED &&
                    $enteredOnBehalf === true;

                if (
                    ! $isDirectDriverReport &&
                    ! $isDelegatedReport
                ) {
                    throw new DomainException(
                        'Only driver or delegated daily reports can be resubmitted.',
                    );
                }

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $driver = Driver::query()
                    ->whereKey(
                        (int) $dailyReport->getAttribute(
                            'performed_by_driver_id',
                        ),
                    )
                    ->first();

                if (
                    $driver === null ||
                    ! $driver->canOperate()
                ) {
                    throw new DomainException(
                        'The performed-by driver is not active.',
                    );
                }

                $driverUserId = (int) $driver->getAttribute(
                    'user_id',
                );

                $originalEntryUserId = (int) $dailyReport->getAttribute(
                    'entered_by_user_id',
                );

                $isActualDriverResubmission =
                    $driverUserId === $enteredByUserId;

                $isOriginalDelegatedActorResubmission =
                    $isDelegatedReport &&
                    $originalEntryUserId === $enteredByUserId;

                if ($isOriginalDelegatedActorResubmission) {
                    $this->assertOrganizationPermission(
                        $enteredByUserId,
                        $organizationId,
                        'daily-reports.enter-for-driver',
                    );
                }

                if (
                    ! $isActualDriverResubmission &&
                    ! $isOriginalDelegatedActorResubmission
                ) {
                    throw new DomainException(
                        'The resubmitting user is not authorized for this daily report.',
                    );
                }

                $this->assertCompleteForSubmission($dailyReport);

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_SUBMITTED,
                );

                $dailyReport->setAttribute(
                    'submitted_at',
                    CarbonImmutable::now(),
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_SUBMITTED,
                    actedByUserId: $enteredByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_SUBMITTED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                        'resubmitted_by_user_id' => $enteredByUserId,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The resubmitted daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    public function approve(
        int $dailyReportId,
        int $approvedByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $approvedByUserId,
            'Approving user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Approval reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $approvedByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $approvedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $approvedByUserId,
                    $organizationId,
                    'daily-reports.approve',
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_UNDER_REVIEW
                ) {
                    throw new DomainException(
                        'Only daily reports under review can be approved.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_APPROVED,
                );

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $enteredByUserId = (int) $dailyReport->getAttribute(
                    'entered_by_user_id',
                );

                if ($enteredByUserId === $approvedByUserId) {
                    throw new DomainException(
                        'The user who entered a daily report cannot approve the same report.',
                    );
                }

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_APPROVED,
                );

                $dailyReport->setAttribute(
                    'approved_at',
                    CarbonImmutable::now(),
                );

                $dailyReport->setAttribute(
                    'approved_by_user_id',
                    $approvedByUserId,
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_APPROVED,
                    actedByUserId: $approvedByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_APPROVED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                        'approved_by_user_id' => $approvedByUserId,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The approved daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    public function close(
        int $dailyReportId,
        int $closedByUserId,
        int $expectedVersion,
        ?string $reason = null,
    ): DailyReport {
        $organizationId = $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $closedByUserId,
            'Closing user identifier',
        );

        $this->assertPositiveIdentifier(
            $expectedVersion,
            'Expected version',
        );

        $normalizedReason = $this->normalizeNullableText(
            $reason,
            'Closure reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportId,
                $closedByUserId,
                $expectedVersion,
                $normalizedReason,
            ): DailyReport {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $closedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $closedByUserId,
                    $organizationId,
                    'daily-reports.close',
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentStatus = $dailyReport->getAttribute(
                    'status',
                );

                if (! is_string($currentStatus)) {
                    throw new LogicException(
                        'Daily report status is not available.',
                    );
                }

                if (
                    $currentStatus !==
                    DailyReport::STATUS_APPROVED
                ) {
                    throw new DomainException(
                        'Only approved daily reports can be closed.',
                    );
                }

                $this->workflow->assertCanTransition(
                    $currentStatus,
                    DailyReport::STATUS_CLOSED,
                );

                $currentVersion = (int) $dailyReport->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedVersion) {
                    throw new DomainException(
                        sprintf(
                            'Daily report version conflict: expected %d, current %d.',
                            $expectedVersion,
                            $currentVersion,
                        ),
                    );
                }

                $beforeAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $dailyReport->setAttribute(
                    'status',
                    DailyReport::STATUS_CLOSED,
                );

                $dailyReport->setAttribute(
                    'closed_at',
                    CarbonImmutable::now(),
                );

                $dailyReport->saveOrFail();

                $afterAttributes = $this->snapshotAttributes(
                    $dailyReport,
                );

                $changedFields = $this->snapshotBuilder->changedFields(
                    $beforeAttributes,
                    $afterAttributes,
                );

                $eventPayload = $this->eventPayloadBuilder->build(
                    dailyReportId: (int) $dailyReport->getKey(),
                    organizationId: $organizationId,
                    eventType: DailyReportEvent::TYPE_CLOSED,
                    actedByUserId: $closedByUserId,
                    fromStatus: $currentStatus,
                    toStatus: DailyReport::STATUS_CLOSED,
                    reason: $normalizedReason,
                    affectedFields: $changedFields,
                    metadata: [
                        'version_number' => $currentVersion,
                        'closed_by_user_id' => $closedByUserId,
                    ],
                );

                DailyReportEvent::query()->create(
                    $eventPayload,
                );

                return $dailyReport->fresh([
                    'versions',
                    'events',
                ]) ?? throw new LogicException(
                    'The closed daily report could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertAllowedUpdateAttributes(
        array $attributes,
    ): void {
        $unsupportedFields = array_values(
            array_diff(
                array_keys($attributes),
                self::ALLOWED_DRAFT_UPDATE_ATTRIBUTES,
            ),
        );

        if ($unsupportedFields !== []) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported daily report draft update fields: %s.',
                    implode(', ', $unsupportedFields),
                ),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeDraftUpdateAttributes(
        DailyReport $dailyReport,
        array $attributes,
    ): array {
        $normalized = [];

        if (array_key_exists('route_number', $attributes)) {
            if (! is_string($attributes['route_number'])) {
                throw new InvalidArgumentException(
                    'Route number must be text.',
                );
            }

            $normalizedRoute = $this->routeNumberNormalizer->normalize(
                $attributes['route_number'],
            );

            if (
                mb_strlen(
                    $normalizedRoute['route_number'],
                    'UTF-8',
                ) > 100
            ) {
                throw new InvalidArgumentException(
                    'Route number must not exceed 100 characters.',
                );
            }

            $normalized['route_number'] =
                $normalizedRoute['route_number'];

            $normalized['route_number_normalized'] =
                $normalizedRoute['route_number_normalized'];
        }

        if (array_key_exists('service_date', $attributes)) {
            $serviceDate = $attributes['service_date'];

            if (
                ! is_string($serviceDate) &&
                ! $serviceDate instanceof DateTimeInterface
            ) {
                throw new InvalidArgumentException(
                    'Service date must be text or a date value.',
                );
            }

            $normalized['service_date'] =
                $this->normalizeServiceDate($serviceDate);
        }

        if (
            array_key_exists(
                'completion_confirmed_at',
                $attributes,
            )
        ) {
            $normalized['completion_confirmed_at'] =
                $this->normalizeNullableDateTime(
                    $attributes['completion_confirmed_at'],
                    'Completion confirmation',
                );
        }

        foreach ([
            'departure_time' => 'Departure time',
            'arrival_time' => 'Arrival time',
        ] as $field => $label) {
            if (array_key_exists($field, $attributes)) {
                $normalized[$field] = $this->normalizeNullableTime(
                    $attributes[$field],
                    $label,
                );
            }
        }

        foreach ([
            'loaded_parcels' => 'Loaded parcels',
            'delivered_parcels' => 'Delivered parcels',
            'redirected_parcels' => 'Redirected parcels',
            'undelivered_parcels' => 'Undelivered parcels',
        ] as $field => $label) {
            if (array_key_exists($field, $attributes)) {
                $normalized[$field] =
                    $this->normalizeNullableParcelCount(
                        $attributes[$field],
                        $label,
                    );
            }
        }

        if (array_key_exists('planned_km', $attributes)) {
            $normalized['planned_km'] =
                $this->normalizeNullableKilometres(
                    $attributes['planned_km'],
                    'Planned kilometres',
                );
        }

        $currentActualKm = $dailyReport->getAttribute(
            'actual_km',
        );

        $currentActualKmSource = $dailyReport->getAttribute(
            'actual_km_source',
        );

        $actualKm = array_key_exists('actual_km', $attributes)
            ? $this->normalizeNullableKilometres(
                $attributes['actual_km'],
                'Actual kilometres',
            )
            : $this->normalizeNullableKilometres(
                $currentActualKm,
                'Actual kilometres',
            );

        $actualKmSource = array_key_exists(
            'actual_km_source',
            $attributes,
        )
            ? $this->normalizeActualKmSource(
                $attributes['actual_km_source'],
            )
            : $this->normalizeActualKmSource(
                $currentActualKmSource,
            );

        if (
            ($actualKm === null) !==
            ($actualKmSource === null)
        ) {
            throw new InvalidArgumentException(
                'Actual kilometres and their source must be provided together.',
            );
        }

        if (array_key_exists('actual_km', $attributes)) {
            $normalized['actual_km'] = $actualKm;
        }

        if (
            array_key_exists(
                'actual_km_source',
                $attributes,
            )
        ) {
            $normalized['actual_km_source'] =
                $actualKmSource;
        }

        if (
            array_key_exists(
                'surcharge_amount',
                $attributes,
            )
        ) {
            $normalized['surcharge_amount'] =
                $this->normalizeSurchargeAmount(
                    $attributes['surcharge_amount'],
                );
        }

        if (
            array_key_exists(
                'operational_notes',
                $attributes,
            )
        ) {
            $normalized['operational_notes'] =
                $this->normalizeNullableText(
                    $attributes['operational_notes'],
                    'Operational notes',
                );
        }

        $candidateSurcharge = array_key_exists(
            'surcharge_amount',
            $normalized,
        )
            ? $normalized['surcharge_amount']
            : $dailyReport->getAttribute('surcharge_amount');

        $candidateNotes = array_key_exists(
            'operational_notes',
            $normalized,
        )
            ? $normalized['operational_notes']
            : $dailyReport->getAttribute('operational_notes');

        $this->assertSurchargeNoteConsistency(
            $candidateSurcharge,
            $candidateNotes,
        );

        return $normalized;
    }

    private function assertCompleteForSubmission(
        DailyReport $dailyReport,
    ): void {
        if ($this->effectiveForm->hasBoundConfiguration($dailyReport)) {
            $this->effectiveForm
                ->assertCompleteForSubmission($dailyReport);

            return;
        }

        $requiredAttributes = [
            'completion_confirmed_at',
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
            'planned_km',
            'actual_km',
            'actual_km_source',
        ];

        $missingAttributes = array_values(
            array_filter(
                $requiredAttributes,
                static fn (string $attribute): bool => $dailyReport->getAttribute($attribute) === null,
            ),
        );

        if ($missingAttributes === []) {
            return;
        }

        throw new DomainException(
            sprintf(
                (
                    'Daily report cannot be submitted because mandatory '.
                    'operational values are missing: %s.'
                ),
                implode(', ', $missingAttributes),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotAttributes(
        DailyReport $dailyReport,
    ): array {
        $attributes = $dailyReport->getAttributes();

        foreach ([
            'planned_km',
            'actual_km',
        ] as $kilometresField) {
            $attributes[$kilometresField] =
                $dailyReport->getAttribute($kilometresField);
        }

        $enteredOnBehalf = $dailyReport->getAttribute(
            'entered_on_behalf',
        );

        if (! is_bool($enteredOnBehalf)) {
            throw new LogicException(
                'Daily report entered-on-behalf state is not available.',
            );
        }

        $attributes['entered_on_behalf'] = $enteredOnBehalf;

        $serviceDate = $dailyReport->getAttribute(
            'service_date',
        );

        if (! $serviceDate instanceof DateTimeInterface) {
            throw new LogicException(
                'Daily report service date is not available.',
            );
        }

        $attributes['service_date'] = CarbonImmutable::instance(
            $serviceDate,
        )->toDateString();

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertAllowedAttributes(
        array $attributes,
    ): void {
        $unsupportedFields = array_values(
            array_diff(
                array_keys($attributes),
                self::ALLOWED_DRAFT_ATTRIBUTES,
            ),
        );

        if ($unsupportedFields !== []) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported daily report draft fields: %s.',
                    implode(', ', $unsupportedFields),
                ),
            );
        }
    }

    private function assertPositiveIdentifier(
        int $identifier,
        string $field,
    ): void {
        if ($identifier < 1) {
            throw new InvalidArgumentException(
                $field.' must be a positive integer.',
            );
        }
    }

    private function assertActiveOrganization(
        int $organizationId,
    ): void {
        $exists = Organization::query()
            ->whereKey($organizationId)
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->exists();

        if (! $exists) {
            throw new DomainException(
                'The verified organization is not active.',
            );
        }
    }

    private function assertAuthorizedDraftEntryActor(
        DailyReport $dailyReport,
        Driver $driver,
        int $enteredByUserId,
        int $organizationId,
        string $directFailureMessage,
        string $delegatedFailureMessage,
        bool $allowAuthorizedImport = false,
    ): void {
        $entryMethod = $dailyReport->getAttribute('entry_method');
        $enteredOnBehalf = $dailyReport->getAttribute(
            'entered_on_behalf',
        );

        if (
            ! is_string($entryMethod) ||
            ! is_bool($enteredOnBehalf)
        ) {
            throw new LogicException(
                'Daily report entry actor state is not available.',
            );
        }

        $driverUserId = (int) $driver->getAttribute('user_id');
        $originalEntryUserId = (int) $dailyReport->getAttribute(
            'entered_by_user_id',
        );

        if (
            $entryMethod === DailyReport::ENTRY_METHOD_DRIVER &&
            $enteredOnBehalf === false
        ) {
            if (
                $driverUserId !== $enteredByUserId ||
                $originalEntryUserId !== $enteredByUserId
            ) {
                throw new DomainException($directFailureMessage);
            }

            return;
        }

        if (
            $entryMethod === DailyReport::ENTRY_METHOD_DELEGATED &&
            $enteredOnBehalf === true
        ) {
            if (
                $driverUserId === $enteredByUserId ||
                $originalEntryUserId !== $enteredByUserId
            ) {
                throw new DomainException($delegatedFailureMessage);
            }

            $this->assertOrganizationPermission(
                $enteredByUserId,
                $organizationId,
                'daily-reports.enter-for-driver',
            );

            return;
        }

        if (
            $entryMethod ===
                DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT &&
            $enteredOnBehalf === false
        ) {
            if (! $allowAuthorizedImport) {
                throw new DomainException(
                    'Authorized import daily reports are immutable through ordinary draft editing.',
                );
            }

            if ($originalEntryUserId !== $enteredByUserId) {
                throw new DomainException(
                    'Authorized import draft submission must use the original import actor.',
                );
            }

            $this->assertOrganizationPermission(
                $enteredByUserId,
                $organizationId,
                'daily-reports.enter-for-driver',
            );

            return;
        }

        throw new LogicException(
            'Daily report entry actor state is invalid.',
        );
    }

    private function assertOrganizationPermission(
        int $userId,
        int $organizationId,
        string $permission,
    ): void {
        $previousOrganizationId =
            $this->permissionRegistrar->getPermissionsTeamId();

        try {
            $this->permissionRegistrar->setPermissionsTeamId(
                $organizationId,
            );

            $user = User::query()->find($userId);

            if (! $user instanceof User) {
                throw new DomainException(
                    'The acting user does not exist.',
                );
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            try {
                $hasPermission = $user->hasPermissionTo(
                    $permission,
                );
            } catch (PermissionDoesNotExist) {
                $hasPermission = false;
            }

            if (! $hasPermission) {
                throw new DomainException(
                    sprintf(
                        'The acting user does not have the required organization permission: %s.',
                        $permission,
                    ),
                );
            }
        } finally {
            $this->permissionRegistrar->setPermissionsTeamId(
                $previousOrganizationId,
            );
        }
    }

    private function assertActiveUserMembership(
        int $userId,
        int $organizationId,
    ): void {
        $activeUserExists = User::query()
            ->whereKey($userId)
            ->where('status', User::STATUS_ACTIVE)
            ->exists();

        if (! $activeUserExists) {
            throw new DomainException(
                'The entering user is not active.',
            );
        }

        $memberships = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where(
                'status',
                OrganizationMembership::STATUS_ACTIVE,
            )
            ->get();

        $moment = now();

        $membershipExists = $memberships->contains(
            static fn (
                OrganizationMembership $membership,
            ): bool => $membership->isActiveAt($moment),
        );

        if (! $membershipExists) {
            throw new DomainException(
                'The entering user does not have an active membership '.
                'in the verified organization.',
            );
        }
    }

    private function normalizeServiceDate(
        DateTimeInterface|string $value,
    ): string {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance(
                $value,
            )->toDateString();
        }

        if (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}$/D',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Service date must use the YYYY-MM-DD format.',
            );
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Service date is invalid.',
                0,
                $exception,
            );
        }

        if ($date->toDateString() !== $value) {
            throw new InvalidArgumentException(
                'Service date is invalid.',
            );
        }

        return $value;
    }

    private function normalizeNullableDateTime(
        mixed $value,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            ! is_string($value) &&
            ! $value instanceof DateTimeInterface
        ) {
            throw new InvalidArgumentException(
                $field.' must be a date-time value or null.',
            );
        }

        try {
            return CarbonImmutable::parse(
                $value,
            )->toDateTimeString();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                $field.' is invalid.',
                0,
                $exception,
            );
        }
    }

    private function normalizeNullableParcelCount(
        mixed $value,
        string $field,
    ): ?int {
        if ($value === null) {
            return null;
        }

        if (! is_int($value) || $value < 0) {
            throw new InvalidArgumentException(
                $field.
                ' must be a non-negative integer or null.',
            );
        }

        return $value;
    }

    private function normalizeNullableKilometres(
        mixed $value,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            ! is_int($value) &&
            ! is_float($value) &&
            ! is_string($value)
        ) {
            throw new InvalidArgumentException(
                $field.' must be numeric or null.',
            );
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                $field.' must be numeric or null.',
            );
        }

        $numericValue = (float) $value;

        if (
            ! is_finite($numericValue) ||
            $numericValue < 0 ||
            $numericValue > 99999999.99
        ) {
            throw new InvalidArgumentException(
                $field.' is outside the supported range.',
            );
        }

        return number_format(
            $numericValue,
            2,
            '.',
            '',
        );
    }

    private function normalizeActualKmSource(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            ! is_string($value) ||
            ! in_array(
                $value,
                DailyReport::ACTUAL_KM_SOURCES,
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Actual kilometre source is invalid.',
            );
        }

        return $value;
    }

    private function normalizeNullableTime(
        mixed $value,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('%s must be a time value.', $field),
            );
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must use HH:MM or HH:MM:SS format.',
                    $field,
                ),
            );
        }

        return strlen($value) === 5
            ? $value.':00'
            : $value;
    }

    private function normalizeSurchargeAmount(
        mixed $value,
    ): string {
        if (
            $value === null
            || (is_string($value) && trim($value) === '')
        ) {
            return '0.00';
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                'Surcharge amount must be numeric.',
            );
        }

        $amount = (float) $value;

        if (
            ! is_finite($amount)
            || $amount < 0
            || $amount > 99999999.99
        ) {
            throw new InvalidArgumentException(
                'Surcharge amount must be between 0 and 99999999.99.',
            );
        }

        return number_format(
            round($amount, 2),
            2,
            '.',
            '',
        );
    }

    private function assertSurchargeNoteConsistency(
        mixed $surchargeAmount,
        mixed $operationalNotes,
    ): void {
        if (! is_numeric($surchargeAmount)) {
            throw new LogicException(
                'Daily report surcharge amount is not numeric.',
            );
        }

        if ((float) $surchargeAmount <= 0.0) {
            return;
        }

        if (
            ! is_string($operationalNotes)
            || trim($operationalNotes) === ''
        ) {
            throw new InvalidArgumentException(
                'Operational notes are required when surcharge amount is greater than zero.',
            );
        }
    }

    private function normalizeNullableText(
        mixed $value,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                $field.' must be text or null.',
            );
        }

        $normalizedValue = trim($value);

        return $normalizedValue === ''
            ? null
            : $normalizedValue;
    }
}
