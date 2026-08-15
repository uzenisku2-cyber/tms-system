<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Drivers;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DriverSupervisoryScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_own_organization_scope(): void
    {
        $organization = $this->createOrganization('Master carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $scope = $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        self::assertSame(
            DriverSupervisoryScope::TYPE_ORGANIZATION,
            $scope->scope_type,
        );

        self::assertSame(
            $organization->getKey(),
            $scope->organization_id,
        );

        self::assertSame(
            $organization->getKey(),
            $scope->target_organization_id,
        );

        self::assertNull(
            $scope->organization_relationship_id,
        );

        self::assertSame(
            $supervisor->getKey(),
            $scope->supervisor_user_id,
        );

        self::assertSame(
            $actor->getKey(),
            $scope->created_by_user_id,
        );
    }

    public function test_it_grants_cross_organization_scope_with_explicit_relationship(): void
    {
        $master = $this->createOrganization('Master carrier');
        $subcarrier = $this->createOrganization('Subcarrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
        );

        $scope = $this->service()->grantOrganizationScope(
            organization: $master,
            supervisor: $supervisor,
            targetOrganization: $subcarrier,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
            organizationRelationship: $relationship,
        );

        self::assertSame(
            $subcarrier->getKey(),
            $scope->target_organization_id,
        );

        self::assertSame(
            $relationship->getKey(),
            $scope->organization_relationship_id,
        );
    }

    public function test_cross_organization_scope_requires_explicit_relationship(): void
    {
        $master = $this->createOrganization('Master carrier');
        $subcarrier = $this->createOrganization('Subcarrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Cross-organization supervisory scope requires an explicit organization relationship.',
        );

        $this->service()->grantOrganizationScope(
            organization: $master,
            supervisor: $supervisor,
            targetOrganization: $subcarrier,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
        );
    }

    public function test_cross_organization_scope_rejects_relationship_for_wrong_target(): void
    {
        $master = $this->createOrganization('Master carrier');
        $expectedTarget = $this->createOrganization('Expected target');
        $otherTarget = $this->createOrganization('Other target');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $relationship = $this->createRelationship(
            $master,
            $otherTarget,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Organization relationship does not cover the target organization.',
        );

        $this->service()->grantOrganizationScope(
            organization: $master,
            supervisor: $supervisor,
            targetOrganization: $expectedTarget,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
            organizationRelationship: $relationship,
        );
    }

    public function test_cross_organization_scope_rejects_inactive_relationship(): void
    {
        $master = $this->createOrganization('Master carrier');
        $subcarrier = $this->createOrganization('Subcarrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
            OrganizationRelationship::STATUS_SUSPENDED,
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Organization relationship is not active for the requested supervisory scope.',
        );

        $this->service()->grantOrganizationScope(
            organization: $master,
            supervisor: $supervisor,
            targetOrganization: $subcarrier,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
            organizationRelationship: $relationship,
        );
    }

    public function test_it_grants_driver_scope_when_driver_is_assigned_to_own_organization(): void
    {
        $organization = $this->createOrganization('Own carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();
        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
            Carbon::parse('2026-08-01'),
        );

        $scope = $this->service()->grantDriverScope(
            organization: $organization,
            supervisor: $supervisor,
            targetDriver: $driver,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
        );

        self::assertSame(
            DriverSupervisoryScope::TYPE_DRIVER,
            $scope->scope_type,
        );

        self::assertSame(
            $driver->getKey(),
            $scope->target_driver_id,
        );

        self::assertNull(
            $scope->target_organization_id,
        );

        self::assertNull(
            $scope->organization_relationship_id,
        );
    }

    public function test_driver_scope_rejects_driver_without_required_assignment(): void
    {
        $organization = $this->createOrganization('Own carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();
        $driver = $this->createDriver();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Target driver is not assigned to the required organization at the start of the supervisory scope.',
        );

        $this->service()->grantDriverScope(
            organization: $organization,
            supervisor: $supervisor,
            targetDriver: $driver,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
        );
    }

    public function test_it_grants_cross_organization_driver_scope(): void
    {
        $master = $this->createOrganization('Master carrier');
        $subcarrier = $this->createOrganization('Subcarrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();
        $driver = $this->createDriver();

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
        );

        $this->assignDriver(
            $driver,
            $subcarrier,
            Carbon::parse('2026-08-01'),
        );

        $scope = $this->service()->grantDriverScope(
            organization: $master,
            supervisor: $supervisor,
            targetDriver: $driver,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
            organizationRelationship: $relationship,
        );

        self::assertSame(
            $driver->getKey(),
            $scope->target_driver_id,
        );

        self::assertSame(
            $relationship->getKey(),
            $scope->organization_relationship_id,
        );

        self::assertSame(
            $master->getKey(),
            $scope->organization_id,
        );
    }

    public function test_cross_organization_driver_scope_rejects_driver_assigned_elsewhere(): void
    {
        $master = $this->createOrganization('Master carrier');
        $subcarrier = $this->createOrganization('Subcarrier');
        $different = $this->createOrganization('Different carrier');

        $supervisor = User::factory()->create();
        $actor = User::factory()->create();
        $driver = $this->createDriver();

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
        );

        $this->assignDriver(
            $driver,
            $different,
            Carbon::parse('2026-08-01'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Target driver is not assigned to the required organization at the start of the supervisory scope.',
        );

        $this->service()->grantDriverScope(
            organization: $master,
            supervisor: $supervisor,
            targetDriver: $driver,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-05'),
            organizationRelationship: $relationship,
        );
    }

    public function test_it_rejects_overlapping_bounded_organization_scopes(): void
    {
        $organization = $this->createOrganization('Overlap carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
            validUntil: Carbon::parse('2026-08-10'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'An overlapping driver supervisory scope already exists.',
        );

        $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-10'),
            validUntil: Carbon::parse('2026-08-20'),
        );
    }

    public function test_it_allows_non_overlapping_bounded_organization_scopes(): void
    {
        $organization = $this->createOrganization('Sequential carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
            validUntil: Carbon::parse('2026-08-10'),
        );

        $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-11'),
            validUntil: Carbon::parse('2026-08-20'),
        );

        $this->assertDatabaseCount(
            'driver_supervisory_scopes',
            2,
        );
    }

    public function test_it_ends_open_scope_with_audit_data(): void
    {
        $organization = $this->createOrganization('Lifecycle carrier');
        $supervisor = User::factory()->create();
        $creator = User::factory()->create();
        $ender = User::factory()->create();

        $scope = $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $creator,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $ended = $this->service()->endScope(
            scope: $scope,
            endedBy: $ender,
            validUntil: Carbon::parse('2026-08-15'),
            reason: 'Supervisory responsibility changed.',
        );

        $endedValidUntil = $ended->getAttribute('valid_until');

        if (! $endedValidUntil instanceof Carbon) {
            self::fail(
                'Expected valid_until to be cast to Carbon.',
            );
        }

        self::assertSame(
            '2026-08-15',
            $endedValidUntil->toDateString(),
        );

        self::assertSame(
            $ender->getKey(),
            $ended->ended_by_user_id,
        );

        self::assertSame(
            'Supervisory responsibility changed.',
            $ended->end_reason,
        );
    }

    public function test_it_rejects_ending_scope_twice(): void
    {
        $organization = $this->createOrganization('Ended carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $scope = $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $scope = $this->service()->endScope(
            scope: $scope,
            endedBy: $actor,
            validUntil: Carbon::parse('2026-08-10'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Driver supervisory scope is already ended.',
        );

        $this->service()->endScope(
            scope: $scope,
            endedBy: $actor,
            validUntil: Carbon::parse('2026-08-11'),
        );
    }

    public function test_it_rejects_end_date_before_scope_start(): void
    {
        $organization = $this->createOrganization('Invalid end carrier');
        $supervisor = User::factory()->create();
        $actor = User::factory()->create();

        $scope = $this->service()->grantOrganizationScope(
            organization: $organization,
            supervisor: $supervisor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-10'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Driver supervisory scope cannot end before it starts.',
        );

        $this->service()->endScope(
            scope: $scope,
            endedBy: $actor,
            validUntil: Carbon::parse('2026-08-09'),
        );
    }

    private function service(): DriverSupervisoryScopeService
    {
        return app(DriverSupervisoryScopeService::class);
    }

    private function createOrganization(
        string $name,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createDriver(): Driver
    {
        $user = User::factory()->create();

        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Supervisory',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'S021-SERVICE-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function assignDriver(
        Driver $driver,
        Organization $organization,
        Carbon $validFrom,
        ?Carbon $validUntil = null,
    ): DriverOrganizationAssignment {
        $actor = User::factory()->create();

        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'valid_from' => $validFrom->toDateString(),
            'valid_until' => $validUntil?->toDateString(),
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
        ]);
    }

    private function createRelationship(
        Organization $source,
        Organization $target,
        string $status = OrganizationRelationship::STATUS_ACTIVE,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $source->getKey(),
            'target_organization_id' => $target->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => $status,
            'valid_from' => Carbon::parse('2026-08-01'),
            'valid_until' => null,
        ]);
    }
}
