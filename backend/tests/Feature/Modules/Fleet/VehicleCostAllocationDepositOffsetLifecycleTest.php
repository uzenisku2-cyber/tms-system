<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Services\VehicleCostAllocationApplicationService;
use App\Modules\Fleet\Services\VehicleCostAllocationDepositOffsetService;
use App\Modules\Fleet\Services\VehicleCostAllocationFinancialHandoffService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class VehicleCostAllocationDepositOffsetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_advance_is_acknowledged_idempotently_without_invoice_bank_or_fund_execution(): void
    {
        $o = Organization::query()->create(['name' => 'S064 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $u = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $o->id, 'user_id' => $u->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $r = app(PermissionRegistrar::class);
        $r->setPermissionsTeamId((int) $o->id);
        $r->forgetCachedPermissions();
        $u->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $u->givePermissionTo(Permission::findOrCreate('users.manage', 'web'));
        $r->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $o->id, 'supervisor_user_id' => $u->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $o->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => $u->id]);
        $v = Vehicle::query()->create(['registration_number' => 'S064', 'vin' => 'S064'.Str::upper(Str::random(13)), 'manufacturer' => 'Test', 'model' => 'Deposit', 'mileage' => 0, 'odometer_unit' => 'km', 'lifecycle_status' => 'active', 'current_revision' => 1, 'active' => true]);
        VehicleOwnership::query()->create(['change_reason' => 'Initial S064 ownership.', 'public_id' => (string) Str::uuid(), 'vehicle_id' => $v->id, 'organization_context_id' => $o->id, 'owner_type' => 'organization', 'owner_organization_id' => $o->id, 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-01-01', 'verification_status' => 'verified', 'recorded_by_user_id' => $u->id, 'revision' => 1]);
        $application = app(VehicleCostAllocationApplicationService::class);
        $draft = $application->create(['vehicle_id' => $v->id, 'source_type' => 'service', 'occurred_on' => '2026-09-08', 'description' => 'Acknowledged advance.', 'currency' => 'CZK', 'lines' => [['cost_component' => 'base_cost', 'responsible_party_type' => 'organization', 'responsible_organization_id' => $o->id, 'net_amount' => '1000.00', 'vat_amount' => '210.00', 'gross_amount' => '1210.00', 'settlement_mode' => 'deposit_offset', 'vat_treatment' => 'standard_rate', 'vat_rate_basis_points' => 2100]]], (int) $o->id, $u);
        $approved = $application->approve($draft['allocation_uid'], 1, (int) $o->id, $u);
        $handoff = app(VehicleCostAllocationFinancialHandoffService::class)->prepare($draft['allocation_uid'], $approved['revision'], (int) $o->id, $u);
        $instruction = $handoff['instructions'][0];
        $input = ['expected_instruction_revision' => 1, 'idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cash', 'payment_reference' => 'CASH-S064', 'evidence_note' => 'Carrier acknowledged the paid advance in cash.', 'vat_disposition' => 'repair_fund_pending'];
        $service = app(VehicleCostAllocationDepositOffsetService::class);
        $first = $service->acknowledge($instruction['public_id'], $input, (int) $o->id, $u);
        $again = $service->acknowledge($instruction['public_id'], $input, (int) $o->id, $u);
        self::assertSame($first['acknowledgement_public_id'], $again['acknowledgement_public_id']);
        self::assertSame('repair_fund_pending', $first['vat_disposition']);
        self::assertFalse($first['invoice_created']);
        self::assertFalse($first['bank_transaction_matched']);
        self::assertFalse($first['repair_fund_movement_created']);
        self::assertFalse($first['settlement_deduction_applied']);
        self::assertDatabaseCount('vehicle_cost_allocation_deposit_offset_acknowledgements', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_deposit_offset_events', 1);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }
}
