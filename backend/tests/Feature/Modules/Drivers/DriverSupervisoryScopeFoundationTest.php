<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Drivers;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DriverSupervisoryScopeFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_persists_own_organization_scope(): void
    {
        $organization = $this->createOrganization('Primary carrier');
        $supervisor = User::factory()->create();

        $scope = DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
            'ended_by_user_id' => null,
            'end_reason' => null,
        ]);

        $this->assertDatabaseHas('driver_supervisory_scopes', [
            'id' => $scope->getKey(),
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
        ]);

        self::assertTrue(
            $scope->organization->is($organization),
        );

        self::assertTrue(
            $scope->supervisor->is($supervisor),
        );

        self::assertTrue(
            $scope->targetOrganization->is($organization),
        );

        self::assertTrue(
            $scope->createdBy->is($supervisor),
        );
    }

    public function test_model_active_window_is_inclusive(): void
    {
        $scope = new DriverSupervisoryScope([
            'valid_from' => '2026-08-05',
            'valid_until' => '2026-08-10',
        ]);

        self::assertFalse(
            $scope->isActiveAt(
                Carbon::parse('2026-08-04'),
            ),
        );

        self::assertTrue(
            $scope->isActiveAt(
                Carbon::parse('2026-08-05'),
            ),
        );

        self::assertTrue(
            $scope->isActiveAt(
                Carbon::parse('2026-08-10'),
            ),
        );

        self::assertFalse(
            $scope->isActiveAt(
                Carbon::parse('2026-08-11'),
            ),
        );
    }

    public function test_database_rejects_invalid_scope_type(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Scope type carrier');
        $supervisor = User::factory()->create();

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => 'invalid',
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_database_rejects_multiple_target_dimensions(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Target carrier');
        $supervisor = User::factory()->create();
        $driver = $this->createDriver();

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => $driver->getKey(),
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_database_rejects_missing_driver_target(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Missing driver carrier');
        $supervisor = User::factory()->create();

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_DRIVER,
            'target_organization_id' => null,
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_database_rejects_invalid_validity_interval(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Validity carrier');
        $supervisor = User::factory()->create();

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-10',
            'valid_until' => '2026-08-09',
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_own_organization_scope_rejects_relationship_reference(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Own carrier');
        $other = $this->createOrganization('Other carrier');
        $supervisor = User::factory()->create();

        $relationship = $this->createRelationship(
            $organization,
            $other,
        );

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => $relationship->getKey(),
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_cross_organization_scope_requires_relationship_reference(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Master carrier');
        $target = $this->createOrganization('Sub carrier');
        $supervisor = User::factory()->create();

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $target->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);
    }

    public function test_database_rejects_duplicate_open_organization_scope(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Duplicate organization');
        $supervisor = User::factory()->create();

        $payload = [
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ];

        DriverSupervisoryScope::query()->create($payload);

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create($payload);
    }

    public function test_database_rejects_duplicate_open_driver_scope(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Duplicate driver');
        $supervisor = User::factory()->create();
        $driver = $this->createDriver();

        $payload = [
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_DRIVER,
            'target_organization_id' => null,
            'target_driver_id' => $driver->getKey(),
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ];

        DriverSupervisoryScope::query()->create($payload);

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create($payload);
    }

    public function test_historical_scope_does_not_block_new_open_scope(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Historical carrier');
        $supervisor = User::factory()->create();

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-07-01',
            'valid_until' => '2026-07-31',
            'created_by_user_id' => $supervisor->getKey(),
            'ended_by_user_id' => $supervisor->getKey(),
            'end_reason' => 'Historical scope ended.',
        ]);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => $supervisor->getKey(),
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => $supervisor->getKey(),
        ]);

        $this->assertDatabaseCount(
            'driver_supervisory_scopes',
            2,
        );
    }

    public function test_database_enforces_supervisor_foreign_key(): void
    {
        $this->requirePostgreSql();

        $organization = $this->createOrganization('Foreign key carrier');

        $this->expectException(QueryException::class);

        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->getKey(),
            'supervisor_user_id' => 999999999,
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->getKey(),
            'target_driver_id' => null,
            'organization_relationship_id' => null,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'created_by_user_id' => 999999999,
        ]);
    }

    private function requirePostgreSql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            self::markTestSkipped(
                'PostgreSQL database invariants are required.',
            );
        }
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
            'license_number' => 'S021-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
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
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);
    }
}
