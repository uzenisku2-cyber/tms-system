<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Services\VehicleCostAllocationApplicationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleCostAllocationApplicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_is_snapshotted_approved_and_never_creates_financial_outputs(): void
    {
        $organization = Organization::query()->create(['name' => 'S061 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->givePermissionTo(Permission::findOrCreate('users.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $organization->id, 'supervisor_user_id' => $actor->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $organization->id, 'target_driver_id' => null, 'organization_relationship_id' => null, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);
        $vehicle = Vehicle::query()->create(['registration_number' => 'S061', 'vin' => 'S061'.Str::upper(Str::random(13)), 'manufacturer' => 'Test', 'model' => 'Allocation', 'mileage' => 0, 'odometer_unit' => 'km', 'lifecycle_status' => 'active', 'current_revision' => 1, 'active' => true]);
        VehicleOwnership::query()->create([
            'change_reason' => 'Initial S061 test ownership.', 'public_id' => (string) Str::uuid(), 'vehicle_id' => $vehicle->id, 'organization_context_id' => $organization->id, 'owner_type' => 'organization', 'owner_organization_id' => $organization->id, 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-01-01', 'verification_status' => 'verified', 'recorded_by_user_id' => $actor->id, 'revision' => 1]);
        $service = app(VehicleCostAllocationApplicationService::class);
        $draft = $service->create(['vehicle_id' => $vehicle->id, 'source_type' => 'service', 'occurred_on' => '2026-09-05', 'description' => 'Service invoice allocation.', 'currency' => 'CZK', 'lines' => [['cost_component' => 'base_cost', 'responsible_party_type' => 'organization', 'responsible_organization_id' => $organization->id, 'net_amount' => '1000.00', 'vat_amount' => '210.00', 'gross_amount' => '1210.00', 'settlement_mode' => 'invoice_required', 'vat_treatment' => 'standard_rate', 'vat_rate_basis_points' => 2100]]], (int) $organization->id, $actor);
        self::assertSame('draft', $draft['status']);
        self::assertSame('1000.00', $draft['net_amount']);
        self::assertFalse($draft['financial_automation_performed']);
        $approved = $service->approve($draft['allocation_uid'], 1, (int) $organization->id, $actor);
        self::assertSame('approved', $approved['status']);
        self::assertSame(2, $approved['revision']);
        self::assertCount(2, $approved['events']);
        self::assertDatabaseCount('vehicle_cost_allocations', 2);
        self::assertDatabaseCount('vehicle_cost_allocation_lines', 2);
        self::assertDatabaseCount('vehicle_cost_allocation_events', 2);
        self::assertDatabaseCount('financial_calculations', 0);
        self::assertDatabaseCount('billing_documents', 0);
        try {
            $service->approve($draft['allocation_uid'], 1, (int) $organization->id, $actor);
            self::fail('Stale revision must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }
    }
}
