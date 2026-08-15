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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class DriverSupervisoryAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_permission_is_required_even_with_membership_and_scope(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = User::factory()->create();

        $this->createActiveMembership(
            $actor,
            $organization,
        );

        $this->ensureManagePermission(
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $organization,
            supervisor: $actor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $this->setPermissionContext(
            $actor,
            $organization,
        );

        $this->assertHttpStatus(
            403,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $organization->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_active_membership_is_required_even_with_permission_and_scope(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = User::factory()->create();

        $this->grantManagePermission(
            $actor,
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $organization,
            supervisor: $actor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $this->setPermissionContext(
            $actor,
            $organization,
        );

        $this->assertHttpStatus(
            403,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $organization->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_driver_assignment_alone_does_not_grant_visibility(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->assertHttpStatus(
            404,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $organization->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_organization_relationship_and_assignment_do_not_grant_visibility_without_scope(): void
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

        $this->createRelationship(
            $master,
            $subcarrier,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $subcarrier,
        );

        $this->assertHttpStatus(
            404,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $master->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_explicit_own_organization_scope_grants_visibility_to_assigned_driver(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $organization,
            supervisor: $actor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $visible = $this->authorizationService()
            ->findVisibleDriver(
                actor: $actor,
                organizationId: (int) $organization->getKey(),
                driverId: (int) $driver->getKey(),
                moment: Carbon::parse('2026-08-05'),
            );

        self::assertTrue(
            $visible->is($driver),
        );
    }

    public function test_organization_scope_does_not_cover_driver_assigned_to_different_organization(): void
    {
        $master = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $different = $this->createOrganization(
            'Different carrier',
            Organization::TYPE_CARRIER,
        );

        $actor = $this->createAuthorizedActor(
            $master,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $different,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $master,
            supervisor: $actor,
            targetOrganization: $master,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $this->assertHttpStatus(
            404,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $master->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_explicit_driver_scope_grants_visibility_to_target_driver(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->scopeService()->grantDriverScope(
            organization: $organization,
            supervisor: $actor,
            targetDriver: $driver,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
        );

        $visible = $this->authorizationService()
            ->findVisibleDriver(
                actor: $actor,
                organizationId: (int) $organization->getKey(),
                driverId: (int) $driver->getKey(),
                moment: Carbon::parse('2026-08-05'),
            );

        self::assertTrue(
            $visible->is($driver),
        );
    }

    public function test_cross_organization_scope_requires_active_relationship_at_authorization_time(): void
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

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $subcarrier,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $master,
            supervisor: $actor,
            targetOrganization: $subcarrier,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
            organizationRelationship: $relationship,
        );

        $visible = $this->authorizationService()
            ->findVisibleDriver(
                actor: $actor,
                organizationId: (int) $master->getKey(),
                driverId: (int) $driver->getKey(),
                moment: Carbon::parse('2026-08-05'),
            );

        self::assertTrue(
            $visible->is($driver),
        );

        $relationship->forceFill([
            'status' => OrganizationRelationship::STATUS_SUSPENDED,
        ])->save();

        $this->assertHttpStatus(
            404,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $master->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    public function test_expired_supervisory_scope_does_not_grant_visibility(): void
    {
        $organization = $this->createOrganization(
            'Master carrier',
            Organization::TYPE_MASTER,
        );

        $actor = $this->createAuthorizedActor(
            $organization,
        );

        $driver = $this->createDriver();

        $this->assignDriver(
            $driver,
            $organization,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $organization,
            supervisor: $actor,
            targetOrganization: $organization,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
            validUntil: Carbon::parse('2026-08-05'),
        );

        $this->assertHttpStatus(
            404,
            fn (): Driver => $this->authorizationService()
                ->findVisibleDriver(
                    actor: $actor,
                    organizationId: (int) $organization->getKey(),
                    driverId: (int) $driver->getKey(),
                    moment: Carbon::parse('2026-08-06'),
                ),
        );
    }

    public function test_explicit_organization_scope_grants_organization_management_target(): void
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

        $relationship = $this->createRelationship(
            $master,
            $subcarrier,
        );

        $this->scopeService()->grantOrganizationScope(
            organization: $master,
            supervisor: $actor,
            targetOrganization: $subcarrier,
            createdBy: $actor,
            validFrom: Carbon::parse('2026-08-01'),
            organizationRelationship: $relationship,
        );

        $manageable = $this->authorizationService()
            ->findManageableOrganization(
                actor: $actor,
                organizationId: (int) $master->getKey(),
                targetOrganizationId: (int) $subcarrier->getKey(),
                moment: Carbon::parse('2026-08-05'),
            );

        self::assertTrue(
            $manageable->is($subcarrier),
        );
    }

    public function test_organization_relationship_does_not_grant_organization_management_without_scope(): void
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

        $this->createRelationship(
            $master,
            $subcarrier,
        );

        $this->assertHttpStatus(
            404,
            fn (): Organization => $this->authorizationService()
                ->findManageableOrganization(
                    actor: $actor,
                    organizationId: (int) $master->getKey(),
                    targetOrganizationId: (int) $subcarrier->getKey(),
                    moment: Carbon::parse('2026-08-05'),
                ),
        );
    }

    private function authorizationService(): DriverSupervisoryAuthorizationService
    {
        return app(
            DriverSupervisoryAuthorizationService::class,
        );
    }

    private function scopeService(): DriverSupervisoryScopeService
    {
        return app(
            DriverSupervisoryScopeService::class,
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

        $this->createActiveMembership(
            $actor,
            $organization,
        );

        $this->grantManagePermission(
            $actor,
            $organization,
        );

        $this->setPermissionContext(
            $actor,
            $organization,
        );

        return $actor;
    }

    private function createActiveMembership(
        User $user,
        Organization $organization,
    ): OrganizationMembership {
        return OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => Carbon::parse('2026-08-01'),
            'valid_until' => null,
        ]);
    }

    private function ensureManagePermission(
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $organization->getKey(),
        );

        $registrar->forgetCachedPermissions();

        Permission::findOrCreate(
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'web',
        );
    }

    private function grantManagePermission(
        User $user,
        Organization $organization,
    ): void {
        $this->ensureManagePermission(
            $organization,
        );

        $permission = Permission::findOrCreate(
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'web',
        );

        $user->givePermissionTo(
            $permission,
        );

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }

    private function setPermissionContext(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $organization->getKey(),
        );

        $registrar->forgetCachedPermissions();

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    private function createDriver(): Driver
    {
        $user = User::factory()->create();

        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Authorization',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'S021-AUTH-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function assignDriver(
        Driver $driver,
        Organization $organization,
    ): DriverOrganizationAssignment {
        $actor = User::factory()->create();

        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
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
            'valid_from' => Carbon::parse('2026-08-01'),
            'valid_until' => null,
        ]);
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function assertHttpStatus(
        int $expectedStatus,
        callable $callback,
    ): void {
        try {
            $callback();

            self::fail(
                "Expected HTTP status {$expectedStatus} denial.",
            );
        } catch (HttpException $exception) {
            self::assertSame(
                $expectedStatus,
                $exception->getStatusCode(),
            );
        }
    }
}
