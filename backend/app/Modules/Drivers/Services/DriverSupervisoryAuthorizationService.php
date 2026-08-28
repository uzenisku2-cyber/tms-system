<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class DriverSupervisoryAuthorizationService
{
    public const CURRENT_MANAGE_PERMISSION = 'users.manage';

    public function findVisibleDriver(
        User $actor,
        int $organizationId,
        int $driverId,
        ?Carbon $moment = null,
    ): Driver {
        $moment ??= now();

        $this->assertManagePermission(
            $actor,
        );

        $this->assertActiveMembership(
            $actor,
            $organizationId,
            $moment,
        );

        $driver = Driver::query()
            ->whereKey($driverId)
            ->firstOrFail();

        if (
            $this->hasActiveDriverScope(
                $actor,
                $organizationId,
                $driverId,
                $moment,
            )
        ) {
            return $driver;
        }

        /*
         * S038-01D
         *
         * Own-driver administration lists drivers through their active user
         * membership. The same driver must remain visible while no historical
         * organization assignment exists yet, otherwise the empty assignment
         * history endpoint incorrectly returns 404.
         */
        $date = $moment->toDateString();

        $hasActiveOwnMembership =
            OrganizationMembership::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'user_id',
                    (int) $driver->getAttribute('user_id'),
                )
                ->where(
                    'status',
                    OrganizationMembership::STATUS_ACTIVE,
                )
                ->whereDate(
                    'valid_from',
                    '<=',
                    $date,
                )
                ->where(
                    static function (Builder $query) use ($date): void {
                        $query
                            ->whereNull('valid_until')
                            ->orWhereDate(
                                'valid_until',
                                '>=',
                                $date,
                            );
                    },
                )
                ->exists();

        if ($hasActiveOwnMembership) {
            return $driver;
        }

        $organizationIds = $this->activeTargetOrganizationIds(
            $actor,
            $organizationId,
            $moment,
        );

        if ($organizationIds === []) {
            abort(404);
        }

        $date = $moment->toDateString();

        $covered = $driver->organizationAssignments()
            ->whereIn(
                'organization_id',
                $organizationIds,
            )
            ->whereDate(
                'valid_from',
                '<=',
                $date,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $date,
                        );
                },
            )
            ->exists();

        if (! $covered) {
            abort(404);
        }

        return $driver;
    }

    public function findManageableOrganization(
        User $actor,
        int $organizationId,
        int $targetOrganizationId,
        ?Carbon $moment = null,
    ): Organization {
        $moment ??= now();

        $this->assertManagePermission(
            $actor,
        );

        $this->assertActiveMembership(
            $actor,
            $organizationId,
            $moment,
        );

        $organization = Organization::query()
            ->whereKey($targetOrganizationId)
            ->firstOrFail();

        $coveredOrganizationIds = $this->activeTargetOrganizationIds(
            $actor,
            $organizationId,
            $moment,
        );

        if (
            ! in_array(
                $targetOrganizationId,
                $coveredOrganizationIds,
                true,
            )
        ) {
            abort(404);
        }

        return $organization;
    }

    /**
     * @return list<int>
     */
    public function visibleDriverOrganizationAssignmentIds(
        User $actor,
        int $organizationId,
        string $requiredPermission,
        ?Carbon $moment = null,
    ): array {
        $moment ??= now();

        if (
            $requiredPermission === ''
            || ! $actor->can($requiredPermission)
        ) {
            abort(
                403,
                'Driver visibility permission is required.',
            );
        }

        $this->assertActiveMembership(
            $actor,
            $organizationId,
            $moment,
        );

        $date = $moment->toDateString();
        $assignmentIds = [];

        $organizationIds = $this->activeTargetOrganizationIds(
            $actor,
            $organizationId,
            $moment,
        );

        if ($organizationIds !== []) {
            $ids = DriverOrganizationAssignment::query()
                ->whereIn('organization_id', $organizationIds)
                ->whereDate('valid_from', '<=', $date)
                ->where(
                    static function (Builder $query) use ($date): void {
                        $query
                            ->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', $date);
                    },
                )
                ->pluck('id');

            foreach ($ids as $assignmentId) {
                $assignmentIds[] = (int) $assignmentId;
            }
        }

        $driverScopes = $this->activeScopeQuery(
            actor: $actor,
            organizationId: $organizationId,
            scopeType: DriverSupervisoryScope::TYPE_DRIVER,
            moment: $moment,
        )->get();

        foreach ($driverScopes as $scope) {
            $driverId = (int) $scope->getAttribute(
                'target_driver_id',
            );

            if ($driverId < 1) {
                continue;
            }

            $relationshipId = $scope->getAttribute(
                'organization_relationship_id',
            );

            $targetOrganizationId = $organizationId;

            if ($relationshipId !== null) {
                $targetOrganizationId =
                    $this->activeRelationshipTargetOrganizationId(
                        relationshipId: (int) $relationshipId,
                        organizationId: $organizationId,
                        moment: $moment,
                    );

                if ($targetOrganizationId === null) {
                    continue;
                }
            }

            $assignment = DriverOrganizationAssignment::query()
                ->where('driver_id', $driverId)
                ->where('organization_id', $targetOrganizationId)
                ->whereDate('valid_from', '<=', $date)
                ->where(
                    static function (Builder $query) use ($date): void {
                        $query
                            ->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', $date);
                    },
                )
                ->first();

            if ($assignment instanceof DriverOrganizationAssignment) {
                $assignmentIds[] = (int) $assignment->getKey();
            }
        }

        sort($assignmentIds);

        return array_values(
            array_unique($assignmentIds),
        );
    }

    private function assertManagePermission(
        User $actor,
    ): void {
        if (
            ! $actor->can(
                self::CURRENT_MANAGE_PERMISSION,
            )
        ) {
            abort(
                403,
                'Driver administration permission is required.',
            );
        }
    }

    private function assertActiveMembership(
        User $actor,
        int $organizationId,
        Carbon $moment,
    ): void {
        $date = $moment->toDateString();

        $active = $actor->organizationMemberships()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->where(
                'status',
                OrganizationMembership::STATUS_ACTIVE,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_from')
                        ->orWhereDate(
                            'valid_from',
                            '<=',
                            $date,
                        );
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $date,
                        );
                },
            )
            ->exists();

        if (! $active) {
            abort(
                403,
                'Active organization membership is required for driver administration.',
            );
        }
    }

    private function hasActiveDriverScope(
        User $actor,
        int $organizationId,
        int $driverId,
        Carbon $moment,
    ): bool {
        $scopes = $this->activeScopeQuery(
            actor: $actor,
            organizationId: $organizationId,
            scopeType: DriverSupervisoryScope::TYPE_DRIVER,
            moment: $moment,
        )
            ->where(
                'target_driver_id',
                $driverId,
            )
            ->get();

        foreach ($scopes as $scope) {
            $relationshipId = $scope->getAttribute(
                'organization_relationship_id',
            );

            if ($relationshipId === null) {
                return true;
            }

            if (
                $this->relationshipIsActive(
                    relationshipId: (int) $relationshipId,
                    organizationId: $organizationId,
                    targetOrganizationId: null,
                    moment: $moment,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function activeTargetOrganizationIds(
        User $actor,
        int $organizationId,
        Carbon $moment,
    ): array {
        $scopes = $this->activeScopeQuery(
            actor: $actor,
            organizationId: $organizationId,
            scopeType: DriverSupervisoryScope::TYPE_ORGANIZATION,
            moment: $moment,
        )->get();

        $organizationIds = [];

        foreach ($scopes as $scope) {
            $targetOrganizationId = (int) $scope->getAttribute(
                'target_organization_id',
            );

            $relationshipId = $scope->getAttribute(
                'organization_relationship_id',
            );

            if ($targetOrganizationId === $organizationId) {
                if ($relationshipId === null) {
                    $organizationIds[] = $targetOrganizationId;
                }

                continue;
            }

            if ($relationshipId === null) {
                continue;
            }

            if (
                $this->relationshipIsActive(
                    relationshipId: (int) $relationshipId,
                    organizationId: $organizationId,
                    targetOrganizationId: $targetOrganizationId,
                    moment: $moment,
                )
            ) {
                $organizationIds[] = $targetOrganizationId;
            }
        }

        return array_values(
            array_unique(
                $organizationIds,
            ),
        );
    }

    /**
     * @return Builder<DriverSupervisoryScope>
     */
    private function activeScopeQuery(
        User $actor,
        int $organizationId,
        string $scopeType,
        Carbon $moment,
    ): Builder {
        $date = $moment->toDateString();

        return DriverSupervisoryScope::query()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->where(
                'supervisor_user_id',
                $actor->getKey(),
            )
            ->where(
                'scope_type',
                $scopeType,
            )
            ->whereDate(
                'valid_from',
                '<=',
                $date,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $date,
                        );
                },
            );
    }

    private function activeRelationshipTargetOrganizationId(
        int $relationshipId,
        int $organizationId,
        Carbon $moment,
    ): ?int {
        $date = $moment->toDateString();

        $relationship = OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->where('source_organization_id', $organizationId)
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where(
                'status',
                OrganizationRelationship::STATUS_ACTIVE,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_from')
                        ->orWhereDate('valid_from', '<=', $date);
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->first();

        if (! $relationship instanceof OrganizationRelationship) {
            return null;
        }

        $targetOrganizationId = (int) $relationship->getAttribute(
            'target_organization_id',
        );

        return $targetOrganizationId > 0
            ? $targetOrganizationId
            : null;
    }

    private function relationshipIsActive(
        int $relationshipId,
        int $organizationId,
        ?int $targetOrganizationId,
        Carbon $moment,
    ): bool {
        $date = $moment->toDateString();

        $query = OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->where(
                'source_organization_id',
                $organizationId,
            )
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where(
                'status',
                OrganizationRelationship::STATUS_ACTIVE,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_from')
                        ->orWhereDate(
                            'valid_from',
                            '<=',
                            $date,
                        );
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $date,
                        );
                },
            );

        if ($targetOrganizationId !== null) {
            $query->where(
                'target_organization_id',
                $targetOrganizationId,
            );
        }

        return $query->exists();
    }
}
