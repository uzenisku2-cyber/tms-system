<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Services\VehicleCostAllocationApplicationService;
use App\Modules\Fleet\Services\VehicleCostAllocationBillingDocumentHandoffService;
use App\Modules\Fleet\Services\VehicleCostAllocationFinancialHandoffService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleCostAllocationBillingDocumentHandoffLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_instruction_creates_one_draft_document_idempotently_without_payment_execution(): void
    {
        $o = Organization::query()->create(['name' => 'S063 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $u = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $o->id, 'user_id' => $u->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $r = app(PermissionRegistrar::class);
        $r->setPermissionsTeamId((int) $o->id);
        $r->forgetCachedPermissions();
        $u->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $u->givePermissionTo(Permission::findOrCreate('users.manage', 'web'));
        $r->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $o->id, 'supervisor_user_id' => $u->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $o->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => $u->id]);
        DB::table('organization_tax_profiles')->insert(['organization_id' => $o->id, 'vat_status' => 'payer', 'vat_rate' => '21.00', 'valid_from' => '2025-01-01', 'source' => 'manual', 'created_by_user_id' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
        $v = Vehicle::query()->create(['registration_number' => 'S063', 'vin' => 'S063'.Str::upper(Str::random(13)), 'manufacturer' => 'Test', 'model' => 'Invoice', 'mileage' => 0, 'odometer_unit' => 'km', 'lifecycle_status' => 'active', 'current_revision' => 1, 'active' => true]);
        VehicleOwnership::query()->create(['change_reason' => 'Initial S063 ownership.', 'public_id' => (string) Str::uuid(), 'vehicle_id' => $v->id, 'organization_context_id' => $o->id, 'owner_type' => 'organization', 'owner_organization_id' => $o->id, 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-01-01', 'verification_status' => 'verified', 'recorded_by_user_id' => $u->id, 'revision' => 1]);
        $a = app(VehicleCostAllocationApplicationService::class);
        $draft = $a->create(['vehicle_id' => $v->id, 'source_type' => 'service', 'occurred_on' => '2026-09-07', 'description' => 'Service invoice.', 'currency' => 'CZK', 'lines' => [['cost_component' => 'base_cost', 'responsible_party_type' => 'organization', 'responsible_organization_id' => $o->id, 'net_amount' => '1000.00', 'vat_amount' => '210.00', 'gross_amount' => '1210.00', 'settlement_mode' => 'invoice_required', 'vat_treatment' => 'standard_rate', 'vat_rate_basis_points' => 2100]]], (int) $o->id, $u);
        $approved = $a->approve($draft['allocation_uid'], 1, (int) $o->id, $u);
        $handoff = app(VehicleCostAllocationFinancialHandoffService::class)->prepare($draft['allocation_uid'], $approved['revision'], (int) $o->id, $u);
        $instruction = $handoff['instructions'][0];
        $input = ['expected_instruction_revision' => 1, 'idempotency_key' => (string) Str::uuid(), 'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'description' => 'Vehicle service recharge', 'vat_rate_basis_points' => 2100];
        $service = app(VehicleCostAllocationBillingDocumentHandoffService::class);
        $first = $service->execute($instruction['public_id'], $input, (int) $o->id, $u);
        $again = $service->execute($instruction['public_id'], $input, (int) $o->id, $u);
        self::assertSame($first['execution_public_id'], $again['execution_public_id']);
        self::assertSame('draft', $first['billing_document']['status']);
        self::assertFalse($first['bank_matching_performed']);
        self::assertFalse($first['payment_marked']);
        self::assertDatabaseCount('billing_documents', 1);
        self::assertDatabaseCount('billing_document_lines', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_handoff_executions', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_handoff_execution_events', 1);
        self::assertDatabaseCount('financial_calculations', 0);
    }
}
