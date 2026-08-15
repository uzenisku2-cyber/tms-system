<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Drivers;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverOrganizationAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-05 12:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_index_rejects_driver_assignment_without_explicit_supervisory_scope(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->getJson(
                $this->assignmentUrl(
                    $driver,
                ),
            );

        $response->assertNotFound();
    }

    public function test_index_succeeds_with_explicit_own_organization_scope(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->getJson(
                $this->assignmentUrl(
                    $driver,
                ),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.current.id',
                (int) $assignment->getKey(),
            );
    }

    public function test_store_succeeds_when_driver_and_target_organization_are_covered(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
            validUntil: '2026-08-05',
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->assignmentUrl(
                    $driver,
                ),
                [
                    'organization_id' => (int) $master->getKey(),
                    'employment_type' => 'employee',
                    'valid_from' => '2026-08-06',
                    'valid_until' => null,
                ],
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'driver_organization_assignments',
            [
                'driver_id' => (int) $driver->getKey(),
                'organization_id' => (int) $master->getKey(),
            ],
        );

        $this->assertTrue(
            DriverOrganizationAssignment::query()
                ->where('driver_id', $driver->getKey())
                ->where('organization_id', $master->getKey())
                ->whereDate('valid_from', '2026-08-06')
                ->whereNull('valid_until')
                ->exists(),
        );
    }

    public function test_active_relationship_does_not_allow_cross_organization_store_without_scope(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $subcarrier = $this->createOrganization(
            'Subcarrier',
            Organization::TYPE_CARRIER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
            validUntil: '2026-08-05',
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->createRelationship(
            source: $master,
            target: $subcarrier,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->assignmentUrl(
                    $driver,
                ),
                [
                    'organization_id' => (int) $subcarrier->getKey(),
                    'valid_from' => '2026-08-06',
                    'valid_until' => null,
                ],
            );

        $response->assertNotFound();

        $this->assertDatabaseMissing(
            'driver_organization_assignments',
            [
                'driver_id' => (int) $driver->getKey(),
                'organization_id' => (int) $subcarrier->getKey(),
                'valid_from' => '2026-08-06',
            ],
        );
    }

    public function test_cross_organization_store_succeeds_with_explicit_scope_and_relationship(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $subcarrier = $this->createOrganization(
            'Subcarrier',
            Organization::TYPE_CARRIER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
            validUntil: '2026-08-05',
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $relationship = $this->createRelationship(
            source: $master,
            target: $subcarrier,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $subcarrier,
            relationship: $relationship,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->postJson(
                $this->assignmentUrl(
                    $driver,
                ),
                [
                    'organization_id' => (int) $subcarrier->getKey(),
                    'valid_from' => '2026-08-06',
                    'valid_until' => null,
                ],
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'driver_organization_assignments',
            [
                'driver_id' => (int) $driver->getKey(),
                'organization_id' => (int) $subcarrier->getKey(),
            ],
        );

        $this->assertTrue(
            DriverOrganizationAssignment::query()
                ->where('driver_id', $driver->getKey())
                ->where('organization_id', $subcarrier->getKey())
                ->whereDate('valid_from', '2026-08-06')
                ->whereNull('valid_until')
                ->exists(),
        );
    }

    public function test_end_succeeds_with_explicit_supervisory_scope(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $assignment = $this->createAssignment(
            driver: $driver,
            organization: $master,
            createdBy: $actor,
        );

        $this->grantOrganizationScope(
            actor: $actor,
            master: $master,
            target: $master,
        );

        $this->actingAsApiUser(
            $actor,
        );

        $response = $this
            ->withHeader(
                'X-Organization-ID',
                (string) $master->getKey(),
            )
            ->patchJson(
                $this->assignmentUrl(
                    $driver,
                ).'/'.$assignment->getKey().'/end',
                [
                    'valid_until' => '2026-08-05',
                    'end_reason' => 'Authorization integration test',
                ],
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'driver_organization_assignments',
            [
                'id' => (int) $assignment->getKey(),
                'ended_by_user_id' => (int) $actor->getKey(),
                'end_reason' => 'Authorization integration test',
            ],
        );

        $this->assertTrue(
            DriverOrganizationAssignment::query()
                ->whereKey($assignment->getKey())
                ->whereDate('valid_until', '2026-08-05')
                ->exists(),
        );
    }

    private function createOrganization(
        string $name,
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createAuthorizedActor(
        Organization $organization,
    ): User {
        $actor = User::factory()->create();

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $organization->getKey(),
        );

        $registrar->forgetCachedPermissions();

        $permission = Permission::findOrCreate(
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'web',
        );

        $actor->givePermissionTo(
            $permission,
        );

        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');

        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function actingAsApiUser(
        User $actor,
    ): void {
        Sanctum::actingAs(
            $actor,
        );
    }

    private function createDriver(): Driver
    {
        $user = User::factory()->create();

        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Assignment',
            'last_name' => 'Authorization',
            'phone' => null,
            'email' => null,
            'license_number' => 'S021-API-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function createAssignment(
        Driver $driver,
        Organization $organization,
        User $createdBy,
        ?string $validUntil = null,
    ): DriverOrganizationAssignment {
        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'valid_from' => '2026-08-01',
            'valid_until' => $validUntil,
            'end_reason' => null,
            'created_by_user_id' => $createdBy->getKey(),
            'ended_by_user_id' => $validUntil !== null
                ? $createdBy->getKey()
                : null,
        ]);
    }

    private function createRelationship(
        Organization $source,
        Organization $target,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $source->getKey(),
            'target_organization_id' => $target->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);
    }

    private function grantOrganizationScope(
        User $actor,
        Organization $master,
        Organization $target,
        ?OrganizationRelationship $relationship = null,
    ): void {
        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $target,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-08-01'),
                organizationRelationship: $relationship,
            );
    }

    private function assignmentUrl(
        Driver $driver,
    ): string {
        return '/api/v1/own-drivers/'
            .$driver->getKey()
            .'/assignments';
    }
}
