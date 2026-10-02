<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Identity;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class OrganizationPeopleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_master_creates_carrier_manager_who_creates_driver_without_financial_access(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
        $admin = User::factory()->create();
        $this->member($master, $admin);
        $this->seed(RolePermissionSeeder::class);
        $this->assignRole($admin, $master, 'super-admin');
        Sanctum::actingAs($admin);
        $masterHeader = ['X-Organization-ID' => (string) $master->getKey()];
        $carrierHeader = ['X-Organization-ID' => (string) $carrier->getKey()];
        $managerEmail = 'manager-'.strtolower(Str::random(8)).'@example.test';
        $created = $this->withHeaders($masterHeader)->postJson(
            '/api/v1/people/carriers/'.$carrier->getKey(),
            ['first_name' => 'Marie', 'last_name' => 'Vedoucí',
                'email' => $managerEmail, 'roles' => ['carrier-admin']],
        )->assertCreated()->assertJsonPath('data.email', $managerEmail)->json('data');
        self::assertTrue(Hash::check($created['initial_password'], User::query()->where('email', $managerEmail)->firstOrFail()->password));
        $this->withHeaders($masterHeader)->postJson(
            '/api/v1/people/carriers/'.$carrier->getKey(),
            ['first_name' => 'Marie', 'last_name' => 'Vedoucí',
                'email' => $managerEmail, 'roles' => ['carrier-admin']],
        )->assertUnprocessable();

        $manager = User::query()->where('email', $managerEmail)->firstOrFail();
        Sanctum::actingAs($manager);
        $this->withHeaders($masterHeader)->getJson('/api/v1/people')->assertForbidden();
        $this->withHeaders($carrierHeader)->getJson('/api/v1/people')->assertOk();
        $managerCapabilities = $this->withHeaders($carrierHeader)
            ->getJson('/api/v1/auth/capabilities')->assertOk()->json('data.permissions');
        self::assertContains('people.manage', $managerCapabilities);
        self::assertContains('daily-reports.view', $managerCapabilities);
        self::assertNotContains('pricing.view', $managerCapabilities);
        self::assertNotContains('compensation.view', $managerCapabilities);
        $this->withHeaders($masterHeader)->getJson('/api/v1/auth/capabilities')
            ->assertForbidden();
        $this->withHeaders($carrierHeader)->getJson('/api/v1/financial-settlement-statements')->assertForbidden();
        $this->withHeaders($carrierHeader)->getJson('/api/v1/carriers')->assertForbidden();
        $driverEmail = 'driver-'.strtolower(Str::random(8)).'@example.test';
        $driverCreated = $this->withHeaders($carrierHeader)->postJson('/api/v1/people', [
            'first_name' => 'Jan', 'last_name' => 'Řidič',
            'email' => $driverEmail, 'roles' => ['driver', 'dispatcher'],
        ])->assertCreated()->json('data');
        $driver = User::query()->where('email', $driverEmail)->firstOrFail();
        self::assertTrue(Hash::check($driverCreated['initial_password'], $driver->password));
        self::assertSame(1, Driver::query()->where('user_id', $driver->getKey())->count());
        Sanctum::actingAs($driver);
        $this->withHeaders($carrierHeader)->getJson('/api/v1/people')->assertForbidden();
        $driverCapabilities = $this->withHeaders($carrierHeader)
            ->getJson('/api/v1/auth/capabilities')->assertOk()->json('data.permissions');
        self::assertNotContains('people.manage', $driverCapabilities);
        self::assertNotContains('pricing.view', $driverCapabilities);
        $this->withHeaders($carrierHeader)->getJson('/api/v1/financial-settlement-statements')->assertForbidden();
        $this->withHeaders($carrierHeader)->getJson('/api/v1/driver-availability-calendar?month=2026-10')->assertOk();
    }

    public function test_master_can_promote_existing_carrier_driver_without_creating_another_account(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $unrelated = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
        $admin = User::factory()->create();
        $driverUser = User::factory()->create();
        $this->member($master, $admin);
        $this->member($carrier, $driverUser);
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Carrier', 'last_name' => 'Driver', 'active' => true,
        ]);
        DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $carrier->getKey(),
            'valid_from' => now()->subDay()->toDateString(),
            'created_by_user_id' => $admin->getKey(),
        ]);
        $this->seed(RolePermissionSeeder::class);
        $this->assignRole($admin, $master, 'super-admin');
        $this->assignRole($driverUser, $carrier, 'driver');
        Sanctum::actingAs($admin);
        $masterHeader = ['X-Organization-ID' => (string) $master->getKey()];
        $carrierUrl = '/api/v1/people/carriers/'.$carrier->getKey();

        $this->withHeaders($masterHeader)->getJson($carrierUrl)
            ->assertOk()->assertJsonPath('data.members.0.roles.0', 'driver');
        $this->withHeaders($masterHeader)->getJson('/api/v1/people/carriers/'.$unrelated->getKey())
            ->assertNotFound();
        $this->withHeaders($masterHeader)->patchJson(
            $carrierUrl.'/'.$driverUser->getKey().'/roles',
            ['roles' => ['driver', 'carrier-admin']],
        )->assertOk()->assertJsonPath('data.organization_id', $carrier->getKey());
        self::assertSame(2, User::query()->count());
        self::assertSame(1, Driver::query()->where('user_id', $driverUser->getKey())->count());

        Sanctum::actingAs($driverUser);
        $carrierHeader = ['X-Organization-ID' => (string) $carrier->getKey()];
        $this->withHeaders($carrierHeader)->getJson('/api/v1/people')->assertOk();
        $this->withHeaders($carrierHeader)->getJson('/api/v1/financial-settlement-statements')->assertForbidden();
        $this->withHeaders($masterHeader)->getJson('/api/v1/people')->assertForbidden();
        $this->withHeaders($carrierHeader)->patchJson(
            '/api/v1/people/'.$driverUser->getKey().'/roles',
            ['roles' => ['driver']],
        )->assertForbidden();
        $this->withHeaders($carrierHeader)->patchJson(
            '/api/v1/people/'.$admin->getKey().'/roles',
            ['roles' => ['dispatcher']],
        )->assertNotFound();
        $this->withHeaders($carrierHeader)->patchJson(
            '/api/v1/people/'.$driverUser->getKey().'/roles',
            ['roles' => ['pricing.view']],
        )->assertUnprocessable();
    }

    private function organization(string $type): Organization
    {
        return Organization::query()->create([
            'name' => 'Organization '.Str::uuid(), 'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $user): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
    }

    private function assignRole(User $user, Organization $organization, string $name): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->getKey());
        $user->assignRole($name);
        $registrar->setPermissionsTeamId(null);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}
