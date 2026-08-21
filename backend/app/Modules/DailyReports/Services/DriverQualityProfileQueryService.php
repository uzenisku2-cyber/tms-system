<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Models\User;
use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

final class DriverQualityProfileQueryService
{
    public function __construct(
        private readonly DriverQualityProfileResolver $resolver,
        private readonly DriverSupervisoryAuthorizationService $authorization,
    ) {}

    /** @return Collection<int, DriverQualityProfile> */
    public function profiles(int $organizationId): Collection
    {
        return DriverQualityProfile::query()
            ->with('versions.components')
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->orderBy('code')
            ->get();
    }

    public function profile(
        int $organizationId,
        string $publicId,
    ): DriverQualityProfile {
        return DriverQualityProfile::query()
            ->with('versions.components')
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /** @return Collection<int, DriverQualityProfileBinding> */
    public function bindings(
        User $actor,
        int $organizationId,
    ): Collection {
        $visibleAssignmentIds = $this->authorization
            ->visibleDriverOrganizationAssignmentIds(
                actor: $actor,
                organizationId: $organizationId,
                requiredPermission: 'daily-reports.view',
            );

        return DriverQualityProfileBinding::query()
            ->with([
                'profile',
                'carrierRelationship.targetOrganization',
                'driverAssignment.driver',
            ])
            ->where('organization_id', $organizationId)
            ->where(
                static function (Builder $query) use (
                    $visibleAssignmentIds,
                ): void {
                    $query->where(
                        'scope_type',
                        '<>',
                        DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
                    )->orWhereIn(
                        'driver_organization_assignment_id',
                        $visibleAssignmentIds,
                    );
                },
            )
            ->orderBy('scope_type')
            ->orderBy('scope_key')
            ->orderByDesc('valid_from')
            ->get();
    }

    /**
     * Return only binding targets the current actor may administer from
     * the active organization context. This keeps Statistics independent
     * from Finance and driver-administration endpoint permissions.
     *
     * @return array{
     *     organization:array{id:int,name:string},
     *     carrier_relationships:list<array{
     *         relationship_id:int,
     *         organization_id:int,
     *         name:string,
     *         valid_from:string|null,
     *         valid_until:string|null
     *     }>,
     *     driver_assignments:list<array{
     *         assignment_id:int,
     *         driver_id:int,
     *         driver_name:string,
     *         organization_id:int,
     *         organization_name:string,
     *         valid_from:string|null,
     *         valid_until:string|null
     *     }>
     * }
     */
    public function bindingTargets(
        User $actor,
        int $organizationId,
    ): array {
        $moment = now();
        $date = $moment->toDateString();
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->firstOrFail();

        $relationships = OrganizationRelationship::query()
            ->with('targetOrganization')
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
                    $query->whereNull('valid_from')
                        ->orWhereDate('valid_from', '<=', $date);
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->orderBy('target_organization_id')
            ->get();

        $carrierRelationships = [];

        foreach ($relationships as $relationship) {
            $carrierRelationships[] = [
                'relationship_id' => (int) $relationship->getKey(),
                'organization_id' => (int) $relationship
                    ->getAttribute('target_organization_id'),
                'name' => (string) $relationship
                    ->targetOrganization
                    ->getAttribute('name'),
                'valid_from' => $this->dateString(
                    $relationship->getAttribute('valid_from'),
                ),
                'valid_until' => $this->dateString(
                    $relationship->getAttribute('valid_until'),
                ),
            ];
        }

        $visibleAssignmentIds = $this->authorization
            ->visibleDriverOrganizationAssignmentIds(
                actor: $actor,
                organizationId: $organizationId,
                requiredPermission: 'daily-reports.view',
                moment: $moment,
            );

        $assignments = DriverOrganizationAssignment::query()
            ->with(['driver', 'organization'])
            ->whereIn('id', $visibleAssignmentIds)
            ->orderBy('driver_id')
            ->orderBy('id')
            ->get();

        $driverAssignments = [];

        foreach ($assignments as $assignment) {
            $driver = $assignment->driver;
            $targetOrganization = $assignment->organization;

            $driverAssignments[] = [
                'assignment_id' => (int) $assignment->getKey(),
                'driver_id' => (int) $assignment
                    ->getAttribute('driver_id'),
                'driver_name' => trim(
                    (string) $driver->getAttribute('first_name').' '.
                    (string) $driver->getAttribute('last_name'),
                ),
                'organization_id' => (int) $assignment
                    ->getAttribute('organization_id'),
                'organization_name' => (string) $targetOrganization
                    ->getAttribute('name'),
                'valid_from' => $this->dateString(
                    $assignment->getAttribute('valid_from'),
                ),
                'valid_until' => $this->dateString(
                    $assignment->getAttribute('valid_until'),
                ),
            ];
        }

        return [
            'organization' => [
                'id' => (int) $organization->getKey(),
                'name' => (string) $organization->getAttribute('name'),
            ],
            'carrier_relationships' => $carrierRelationships,
            'driver_assignments' => $driverAssignments,
        ];
    }

    /**
     * @param  array{
     *     service_date:string,
     *     driver_organization_assignment_id?:int|null,
     *     organization_relationship_id?:int|null
     * }  $data
     */
    public function effective(
        User $actor,
        int $organizationId,
        array $data,
    ): DriverQualityProfileResolution {
        $date = Carbon::parse($data['service_date'])->startOfDay();
        $assignmentId = $data[
            'driver_organization_assignment_id'
        ] ?? null;
        $relationshipId = $data[
            'organization_relationship_id'
        ] ?? null;

        if ($assignmentId !== null) {
            $visibleIds = $this->authorization
                ->visibleDriverOrganizationAssignmentIds(
                    actor: $actor,
                    organizationId: $organizationId,
                    requiredPermission: 'daily-reports.view',
                    moment: $date,
                );

            if (! in_array($assignmentId, $visibleIds, true)) {
                throw (new ModelNotFoundException)
                    ->setModel(DriverOrganizationAssignment::class);
            }
        }

        if ($relationshipId !== null) {
            $this->findRelationship(
                organizationId: $organizationId,
                relationshipId: $relationshipId,
                moment: $date,
            );
        }

        return $this->resolver->resolve(
            organizationId: $organizationId,
            serviceDate: $date,
            driverAssignmentId: $assignmentId,
            carrierRelationshipId: $relationshipId,
        );
    }

    private function findRelationship(
        int $organizationId,
        int $relationshipId,
        Carbon $moment,
    ): OrganizationRelationship {
        $date = $moment->toDateString();

        return OrganizationRelationship::query()
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
                    $query->whereNull('valid_from')
                        ->orWhereDate('valid_from', '<=', $date);
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->firstOrFail();
    }

    private function dateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
