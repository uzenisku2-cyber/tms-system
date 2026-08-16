<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DriverSupervisoryScopeService
{
    public function grantOrganizationScope(
        Organization $organization,
        User $supervisor,
        Organization $targetOrganization,
        User $createdBy,
        Carbon $validFrom,
        ?Carbon $validUntil = null,
        ?OrganizationRelationship $organizationRelationship = null,
    ): DriverSupervisoryScope {
        $this->assertValidInterval(
            $validFrom,
            $validUntil,
        );

        $this->assertOrganizationRelationship(
            $organization,
            $targetOrganization,
            $organizationRelationship,
            $validFrom,
        );

        return DB::transaction(
            function () use (
                $organization,
                $supervisor,
                $targetOrganization,
                $createdBy,
                $validFrom,
                $validUntil,
                $organizationRelationship,
            ): DriverSupervisoryScope {
                $this->lockSupervisor(
                    (int) $supervisor->getKey(),
                );

                $this->assertNoOverlappingScope(
                    organizationId: (int) $organization->getKey(),
                    supervisorUserId: (int) $supervisor->getKey(),
                    scopeType: DriverSupervisoryScope::TYPE_ORGANIZATION,
                    targetColumn: 'target_organization_id',
                    targetId: (int) $targetOrganization->getKey(),
                    validFrom: $validFrom,
                    validUntil: $validUntil,
                );

                return DriverSupervisoryScope::query()->create([
                    'organization_id' => $organization->getKey(),
                    'supervisor_user_id' => $supervisor->getKey(),
                    'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
                    'target_organization_id' => $targetOrganization->getKey(),
                    'target_driver_id' => null,
                    'organization_relationship_id' => (
                        $organizationRelationship?->getKey()
                    ),
                    'valid_from' => $validFrom->toDateString(),
                    'valid_until' => $validUntil?->toDateString(),
                    'created_by_user_id' => $createdBy->getKey(),
                    'ended_by_user_id' => null,
                    'end_reason' => null,
                ]);
            },
        );
    }

    public function grantDriverScope(
        Organization $organization,
        User $supervisor,
        Driver $targetDriver,
        User $createdBy,
        Carbon $validFrom,
        ?Carbon $validUntil = null,
        ?OrganizationRelationship $organizationRelationship = null,
    ): DriverSupervisoryScope {
        $this->assertValidInterval(
            $validFrom,
            $validUntil,
        );

        $targetOrganizationId = (int) $organization->getKey();

        if ($organizationRelationship !== null) {
            $this->assertCrossOrganizationRelationship(
                $organization,
                $organizationRelationship,
                $validFrom,
            );

            $targetOrganizationId = (
                (int) $organizationRelationship->target_organization_id
            );
        }

        $this->assertDriverAssignment(
            $targetDriver,
            $targetOrganizationId,
            $validFrom,
        );

        return DB::transaction(
            function () use (
                $organization,
                $supervisor,
                $targetDriver,
                $createdBy,
                $validFrom,
                $validUntil,
                $organizationRelationship,
            ): DriverSupervisoryScope {
                $this->lockSupervisor(
                    (int) $supervisor->getKey(),
                );

                $this->assertNoOverlappingScope(
                    organizationId: (int) $organization->getKey(),
                    supervisorUserId: (int) $supervisor->getKey(),
                    scopeType: DriverSupervisoryScope::TYPE_DRIVER,
                    targetColumn: 'target_driver_id',
                    targetId: (int) $targetDriver->getKey(),
                    validFrom: $validFrom,
                    validUntil: $validUntil,
                );

                return DriverSupervisoryScope::query()->create([
                    'organization_id' => $organization->getKey(),
                    'supervisor_user_id' => $supervisor->getKey(),
                    'scope_type' => DriverSupervisoryScope::TYPE_DRIVER,
                    'target_organization_id' => null,
                    'target_driver_id' => $targetDriver->getKey(),
                    'organization_relationship_id' => (
                        $organizationRelationship?->getKey()
                    ),
                    'valid_from' => $validFrom->toDateString(),
                    'valid_until' => $validUntil?->toDateString(),
                    'created_by_user_id' => $createdBy->getKey(),
                    'ended_by_user_id' => null,
                    'end_reason' => null,
                ]);
            },
        );
    }

    public function endScope(
        DriverSupervisoryScope $scope,
        User $endedBy,
        Carbon $validUntil,
        ?string $reason = null,
    ): DriverSupervisoryScope {
        return DB::transaction(
            function () use (
                $scope,
                $endedBy,
                $validUntil,
                $reason,
            ): DriverSupervisoryScope {
                $lockedScope = DriverSupervisoryScope::query()
                    ->whereKey($scope->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedScope->valid_until !== null) {
                    throw new DomainException(
                        'Driver supervisory scope is already ended.',
                    );
                }

                $validFrom = Carbon::parse(
                    $lockedScope->valid_from,
                );

                if (
                    $validUntil->toDateString()
                    < $validFrom->toDateString()
                ) {
                    throw new DomainException(
                        'Driver supervisory scope cannot end before it starts.',
                    );
                }

                $lockedScope->forceFill([
                    'valid_until' => $validUntil->toDateString(),
                    'ended_by_user_id' => $endedBy->getKey(),
                    'end_reason' => $reason,
                ])->save();

                return $lockedScope->refresh();
            },
        );
    }

    private function lockSupervisor(
        int $supervisorUserId,
    ): void {
        User::query()
            ->whereKey($supervisorUserId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertValidInterval(
        Carbon $validFrom,
        ?Carbon $validUntil,
    ): void {
        if (
            $validUntil !== null
            && $validUntil->toDateString() < $validFrom->toDateString()
        ) {
            throw new DomainException(
                'Driver supervisory scope validity interval is invalid.',
            );
        }
    }

    private function assertOrganizationRelationship(
        Organization $organization,
        Organization $targetOrganization,
        ?OrganizationRelationship $relationship,
        Carbon $moment,
    ): void {
        $organizationId = (int) $organization->getKey();
        $targetOrganizationId = (int) $targetOrganization->getKey();

        if ($organizationId === $targetOrganizationId) {
            if ($relationship !== null) {
                throw new DomainException(
                    'Own-organization supervisory scope must not reference an organization relationship.',
                );
            }

            return;
        }

        if ($relationship === null) {
            throw new DomainException(
                'Cross-organization supervisory scope requires an explicit organization relationship.',
            );
        }

        $this->assertCrossOrganizationRelationship(
            $organization,
            $relationship,
            $moment,
        );

        if (
            (int) $relationship->target_organization_id
            !== $targetOrganizationId
        ) {
            throw new DomainException(
                'Organization relationship does not cover the target organization.',
            );
        }
    }

    private function assertCrossOrganizationRelationship(
        Organization $organization,
        OrganizationRelationship $relationship,
        Carbon $moment,
    ): void {
        if (
            (int) $relationship->source_organization_id
            !== (int) $organization->getKey()
        ) {
            throw new DomainException(
                'Organization relationship does not belong to the supervisory organization.',
            );
        }

        if (
            $relationship->relationship_type
            !== OrganizationRelationship::TYPE_SUBCONTRACTING
        ) {
            throw new DomainException(
                'Organization relationship does not support driver supervision.',
            );
        }

        if (! $relationship->isActiveAt($moment)) {
            throw new DomainException(
                'Organization relationship is not active for the requested supervisory scope.',
            );
        }
    }

    private function assertDriverAssignment(
        Driver $driver,
        int $organizationId,
        Carbon $moment,
    ): void {
        $date = $moment->toDateString();

        $exists = $driver->organizationAssignments()
            ->where('organization_id', $organizationId)
            ->whereDate('valid_from', '<=', $date)
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->exists();

        if (! $exists) {
            throw new DomainException(
                'Target driver is not assigned to the required organization at the start of the supervisory scope.',
            );
        }
    }

    private function assertNoOverlappingScope(
        int $organizationId,
        int $supervisorUserId,
        string $scopeType,
        string $targetColumn,
        int $targetId,
        Carbon $validFrom,
        ?Carbon $validUntil,
    ): void {
        $startDate = $validFrom->toDateString();

        $query = DriverSupervisoryScope::query()
            ->where('organization_id', $organizationId)
            ->where('supervisor_user_id', $supervisorUserId)
            ->where('scope_type', $scopeType)
            ->where($targetColumn, $targetId)
            ->where(
                static function (Builder $query) use ($startDate): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $startDate);
                },
            );

        if ($validUntil !== null) {
            $query->whereDate(
                'valid_from',
                '<=',
                $validUntil->toDateString(),
            );
        }

        if ($query->lockForUpdate()->exists()) {
            throw new DomainException(
                'An overlapping driver supervisory scope already exists.',
            );
        }
    }
}
