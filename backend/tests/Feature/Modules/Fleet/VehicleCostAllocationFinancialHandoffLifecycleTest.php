<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Services\VehicleCostAllocationApplicationService;
use App\Modules\Fleet\Services\VehicleCostAllocationFinancialHandoffService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleCostAllocationFinancialHandoffLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_allocation_is_prepared_idempotently_without_financial_execution(): void
    {
        $o = Organization::query()->create(['name' => 'S062 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $u = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $o->id, 'user_id' => $u->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $r = app(PermissionRegistrar::class);
        $r->setPermissionsTeamId((int) $o->id);
        $r->forgetCachedPermissions();
        $u->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $u->givePermissionTo(Permission::findOrCreate('users.manage', 'web'));
        $u->unsetRelation('permissions');
        $r->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $o->id, 'supervisor_user_id' => $u->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $o->id, 'target_driver_id' => null, 'organization_relationship_id' => null, 'valid_from' => '2025-01-01', 'created_by_user_id' => $u->id]);
        $v = Vehicle::query()->create(['registration_number' => 'S062', 'vin' => 'S062'.Str::upper(Str::random(13)), 'manufacturer' => 'Test', 'model' => 'Handoff', 'mileage' => 0, 'odometer_unit' => 'km', 'lifecycle_status' => 'active', 'current_revision' => 1, 'active' => true]);
        VehicleOwnership::query()->create(['change_reason' => 'Initial S062 ownership.', 'public_id' => (string) Str::uuid(), 'vehicle_id' => $v->id, 'organization_context_id' => $o->id, 'owner_type' => 'organization', 'owner_organization_id' => $o->id, 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-01-01', 'verification_status' => 'verified', 'recorded_by_user_id' => $u->id, 'revision' => 1]);
        $a = app(VehicleCostAllocationApplicationService::class);
        $d = $a->create(['vehicle_id' => $v->id, 'source_type' => 'service', 'occurred_on' => '2026-09-07', 'description' => 'Service split.', 'currency' => 'CZK', 'lines' => [['cost_component' => 'base_cost', 'responsible_party_type' => 'organization', 'responsible_organization_id' => $o->id, 'net_amount' => '1000.00', 'vat_amount' => '210.00', 'gross_amount' => '1210.00', 'settlement_mode' => 'invoice_required', 'vat_treatment' => 'standard_rate', 'vat_rate_basis_points' => 2100], ['cost_component' => 'vat', 'responsible_party_type' => 'internal', 'net_amount' => '0.00', 'vat_amount' => '210.00', 'gross_amount' => '210.00', 'settlement_mode' => 'repair_fund_reserve', 'vat_treatment' => 'pending_review']]], (int) $o->id, $u);
        $approved = $a->approve($d['allocation_uid'], 1, (int) $o->id, $u);
        $s = app(VehicleCostAllocationFinancialHandoffService::class);
        $first = $s->prepare($d['allocation_uid'], $approved['revision'], (int) $o->id, $u);
        $again = $s->prepare($d['allocation_uid'], $approved['revision'], (int) $o->id, $u);
        self::assertSame($first['handoff_uid'], $again['handoff_uid']);
        self::assertCount(2, $first['instructions']);
        self::assertTrue($first['instructions'][0]['requires_invoice']);
        self::assertFalse($first['instructions'][0]['bank_matching_eligible']);
        self::assertFalse($first['financial_automation_performed']);
        self::assertDatabaseCount('vehicle_cost_allocation_financial_handoffs', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_financial_handoff_instructions', 2);
        self::assertDatabaseCount('vehicle_cost_allocation_financial_handoff_events', 1);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }
}
