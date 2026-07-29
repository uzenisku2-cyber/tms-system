<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\DailyReportRequestRules;
use App\Modules\Drivers\Models\Driver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LogicException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

final class DailyReportWriteService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DailyReportPersistenceService $persistence,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(User $actor, array $input): DailyReport
    {
        $actorId = $this->actorId($actor);

        $driverId = $this->requiredInteger(
            $input,
            'performed_by_driver_id',
        );

        $driver = Driver::query()->find($driverId);

        if (! $driver instanceof Driver) {
            throw (new ModelNotFoundException)->setModel(
                Driver::class,
                [$driverId],
            );
        }

        $routeNumber = $this->requiredString(
            $input,
            'route_number',
        );

        $serviceDate = $this->requiredString(
            $input,
            'service_date',
        );

        $attributes = $this->mutableAttributes($input);

        unset(
            $attributes['route_number'],
            $attributes['service_date'],
        );

        $reason = $this->nullableString(
            $input,
            'reason',
        );

        if ($this->driverUserId($driver) === $actorId) {
            $this->assertPermission(
                $actor,
                'daily-reports.create',
            );

            return $this->persistence->createDraft(
                performedByDriverId: $driverId,
                enteredByUserId: $actorId,
                routeNumber: $routeNumber,
                serviceDate: $serviceDate,
                attributes: $attributes,
                reason: $reason,
            );
        }

        $this->assertPermission(
            $actor,
            'daily-reports.enter-for-driver',
        );

        return $this->persistence->createDelegatedDraft(
            performedByDriverId: $driverId,
            enteredByUserId: $actorId,
            routeNumber: $routeNumber,
            serviceDate: $serviceDate,
            attributes: $attributes,
            reason: $reason,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        $this->assertOriginalEntryActorPermission(
            $actor,
            $dailyReport,
            'daily-reports.update',
        );

        return $this->persistence->updateDraft(
            dailyReportId: $this->reportId($dailyReport),
            enteredByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            attributes: $this->mutableAttributes($input),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function submit(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        $this->assertOriginalEntryActorPermission(
            $actor,
            $dailyReport,
            'daily-reports.submit',
        );

        return $this->persistence->submitDraft(
            dailyReportId: $this->reportId($dailyReport),
            enteredByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function startReview(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        return $this->persistence->startReview(
            dailyReportId: $this->reportId($dailyReport),
            reviewedByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function requestCorrection(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        return $this->persistence->requestCorrection(
            dailyReportId: $this->reportId($dailyReport),
            requestedByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function recordCorrection(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        $this->assertCorrectionActorPermission(
            $actor,
            $dailyReport,
            'daily-reports.update',
        );

        return $this->persistence->recordCorrection(
            dailyReportId: $this->reportId($dailyReport),
            enteredByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            attributes: $this->mutableAttributes($input),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function resubmit(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        $this->assertCorrectionActorPermission(
            $actor,
            $dailyReport,
            'daily-reports.submit',
        );

        return $this->persistence->resubmitCorrected(
            dailyReportId: $this->reportId($dailyReport),
            enteredByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function approve(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        return $this->persistence->approve(
            dailyReportId: $this->reportId($dailyReport),
            approvedByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function close(
        User $actor,
        string $publicId,
        array $input,
    ): DailyReport {
        $dailyReport = $this->findReport($publicId);

        return $this->persistence->close(
            dailyReportId: $this->reportId($dailyReport),
            closedByUserId: $this->actorId($actor),
            expectedVersion: $this->requiredInteger(
                $input,
                'expected_version',
            ),
            reason: $this->nullableString(
                $input,
                'reason',
            ),
        );
    }

    private function findReport(string $publicId): DailyReport
    {
        return DailyReport::query()
            ->forOrganization(
                $this->organizationContext->requireId(),
            )
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    private function assertOriginalEntryActorPermission(
        User $actor,
        DailyReport $dailyReport,
        string $directPermission,
    ): void {
        $actorId = $this->actorId($actor);

        $driverUserId = $this->reportDriverUserId(
            $dailyReport,
        );

        $enteredByUserId = $this->reportInteger(
            $dailyReport,
            'entered_by_user_id',
        );

        $entryMethod = $this->reportString(
            $dailyReport,
            'entry_method',
        );

        $enteredOnBehalf = $this->reportBoolean(
            $dailyReport,
            'entered_on_behalf',
        );

        if (
            $entryMethod === DailyReport::ENTRY_METHOD_DRIVER
            && $enteredOnBehalf === false
            && $actorId === $driverUserId
            && $actorId === $enteredByUserId
        ) {
            $this->assertPermission(
                $actor,
                $directPermission,
            );

            return;
        }

        if (
            $entryMethod === DailyReport::ENTRY_METHOD_DELEGATED
            && $enteredOnBehalf === true
            && $actorId !== $driverUserId
            && $actorId === $enteredByUserId
        ) {
            $this->assertPermission(
                $actor,
                'daily-reports.enter-for-driver',
            );

            return;
        }

        throw new AuthorizationException(
            'The authenticated user cannot modify this daily report.',
        );
    }

    private function assertCorrectionActorPermission(
        User $actor,
        DailyReport $dailyReport,
        string $driverPermission,
    ): void {
        $actorId = $this->actorId($actor);

        $driverUserId = $this->reportDriverUserId(
            $dailyReport,
        );

        if ($actorId === $driverUserId) {
            $this->assertPermission(
                $actor,
                $driverPermission,
            );

            return;
        }

        $enteredByUserId = $this->reportInteger(
            $dailyReport,
            'entered_by_user_id',
        );

        $entryMethod = $this->reportString(
            $dailyReport,
            'entry_method',
        );

        $enteredOnBehalf = $this->reportBoolean(
            $dailyReport,
            'entered_on_behalf',
        );

        if (
            $entryMethod === DailyReport::ENTRY_METHOD_DELEGATED
            && $enteredOnBehalf === true
            && $actorId === $enteredByUserId
        ) {
            $this->assertPermission(
                $actor,
                'daily-reports.enter-for-driver',
            );

            return;
        }

        throw new AuthorizationException(
            'The authenticated user cannot correct this daily report.',
        );
    }

    private function assertPermission(
        User $actor,
        string $permission,
    ): void {
        $organizationId =
            $this->organizationContext->requireId();

        $previousOrganizationId =
            $this->permissionRegistrar->getPermissionsTeamId();

        try {
            $this->permissionRegistrar->setPermissionsTeamId(
                $organizationId,
            );

            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');

            try {
                $allowed = $actor->hasPermissionTo(
                    $permission,
                );
            } catch (PermissionDoesNotExist) {
                $allowed = false;
            }

            if (! $allowed) {
                throw new AuthorizationException(
                    sprintf(
                        'Missing organization permission: %s.',
                        $permission,
                    ),
                );
            }
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');

            $this->permissionRegistrar->setPermissionsTeamId(
                $previousOrganizationId,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mutableAttributes(array $input): array
    {
        return array_intersect_key(
            $input,
            array_fill_keys(
                DailyReportRequestRules::MUTABLE_FIELDS,
                true,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function requiredInteger(
        array $input,
        string $key,
    ): int {
        return $this->positiveInteger(
            $input[$key] ?? null,
            sprintf(
                'Validated integer input is unavailable: %s.',
                $key,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function requiredString(
        array $input,
        string $key,
    ): string {
        $value = $input[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new LogicException(
                sprintf(
                    'Validated string input is unavailable: %s.',
                    $key,
                ),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableString(
        array $input,
        string $key,
    ): ?string {
        $value = $input[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                sprintf(
                    'Validated nullable string input is invalid: %s.',
                    $key,
                ),
            );
        }

        return $value;
    }

    private function actorId(User $actor): int
    {
        return $this->positiveInteger(
            $actor->getKey(),
            'Authenticated user identifier is unavailable.',
        );
    }

    private function reportId(DailyReport $dailyReport): int
    {
        return $this->positiveInteger(
            $dailyReport->getKey(),
            'Daily report identifier is unavailable.',
        );
    }

    private function reportDriverUserId(
        DailyReport $dailyReport,
    ): int {
        $driverId = $this->reportInteger(
            $dailyReport,
            'performed_by_driver_id',
        );

        $driver = Driver::query()->find($driverId);

        if (! $driver instanceof Driver) {
            throw (new ModelNotFoundException)->setModel(
                Driver::class,
                [$driverId],
            );
        }

        return $this->driverUserId($driver);
    }

    private function driverUserId(Driver $driver): int
    {
        return $this->positiveInteger(
            $driver->getAttribute('user_id'),
            'Driver user identifier is unavailable.',
        );
    }

    private function reportInteger(
        DailyReport $dailyReport,
        string $attribute,
    ): int {
        return $this->positiveInteger(
            $dailyReport->getAttribute($attribute),
            sprintf(
                'Daily report integer attribute is unavailable: %s.',
                $attribute,
            ),
        );
    }

    private function positiveInteger(
        mixed $value,
        string $message,
    ): int {
        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && preg_match('/^[1-9][0-9]*$/', $value) === 1
        ) {
            return (int) $value;
        }

        throw new LogicException($message);
    }

    private function reportString(
        DailyReport $dailyReport,
        string $attribute,
    ): string {
        $value = $dailyReport->getAttribute($attribute);

        if (! is_string($value) || $value === '') {
            throw new LogicException(
                sprintf(
                    'Daily report string attribute is unavailable: %s.',
                    $attribute,
                ),
            );
        }

        return $value;
    }

    private function reportBoolean(
        DailyReport $dailyReport,
        string $attribute,
    ): bool {
        $value = $dailyReport->getAttribute($attribute);

        if (! is_bool($value)) {
            throw new LogicException(
                sprintf(
                    'Daily report boolean attribute is unavailable: %s.',
                    $attribute,
                ),
            );
        }

        return $value;
    }
}
