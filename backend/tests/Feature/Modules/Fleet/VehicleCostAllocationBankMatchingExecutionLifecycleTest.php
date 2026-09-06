<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use App\Modules\Fleet\Services\VehicleCostAllocationApplicationService;
use App\Modules\Fleet\Services\VehicleCostAllocationBankMatchingExecutionService;
use App\Modules\Fleet\Services\VehicleCostAllocationBankMatchingHandoffService;
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

final class VehicleCostAllocationBankMatchingExecutionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepared_handoff_matches_recorded_transaction_idempotently_without_marking_payment(): void
    {
        $organization = Organization::query()->create(['name' => 'S067 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->givePermissionTo(Permission::findOrCreate('users.manage', 'web'));
        $registrar->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $organization->id, 'supervisor_user_id' => $actor->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $organization->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);
        DB::table('organization_tax_profiles')->insert(['organization_id' => $organization->id, 'vat_status' => 'payer', 'vat_rate' => '21.00', 'valid_from' => '2025-01-01', 'source' => 'manual', 'created_by_user_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);

        $vehicle = Vehicle::query()->create(['registration_number' => 'S067', 'vin' => 'S067'.Str::upper(Str::random(13)), 'manufacturer' => 'Test', 'model' => 'Bank match', 'mileage' => 0, 'odometer_unit' => 'km', 'lifecycle_status' => 'active', 'current_revision' => 1, 'active' => true]);
        VehicleOwnership::query()->create(['change_reason' => 'Initial S067 ownership.', 'public_id' => (string) Str::uuid(), 'vehicle_id' => $vehicle->id, 'organization_context_id' => $organization->id, 'owner_type' => 'organization', 'owner_organization_id' => $organization->id, 'ownership_share_basis_points' => 10000, 'valid_from' => '2025-01-01', 'verification_status' => 'verified', 'recorded_by_user_id' => $actor->id, 'revision' => 1]);

        $allocationService = app(VehicleCostAllocationApplicationService::class);
        $draft = $allocationService->create(['vehicle_id' => $vehicle->id, 'source_type' => 'service', 'occurred_on' => '2026-09-09', 'description' => 'Service invoice bank match.', 'currency' => 'CZK', 'lines' => [['cost_component' => 'base_cost', 'responsible_party_type' => 'organization', 'responsible_organization_id' => $organization->id, 'net_amount' => '1000.00', 'vat_amount' => '210.00', 'gross_amount' => '1210.00', 'settlement_mode' => 'invoice_required', 'vat_treatment' => 'standard_rate', 'vat_rate_basis_points' => 2100]]], (int) $organization->id, $actor);
        $approved = $allocationService->approve($draft['allocation_uid'], 1, (int) $organization->id, $actor);
        $financialHandoff = app(VehicleCostAllocationFinancialHandoffService::class)->prepare($draft['allocation_uid'], $approved['revision'], (int) $organization->id, $actor);
        $instruction = $financialHandoff['instructions'][0];

        app(VehicleCostAllocationBillingDocumentHandoffService::class)->execute($instruction['public_id'], ['expected_instruction_revision' => 1, 'idempotency_key' => (string) Str::uuid(), 'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'description' => 'Vehicle service recharge', 'vat_rate_basis_points' => 2100], (int) $organization->id, $actor);

        $bankReference = 'BANK-S067-001';
        $handoff = app(VehicleCostAllocationBankMatchingHandoffService::class)->prepare($instruction['public_id'], ['expected_instruction_revision' => 1, 'idempotency_key' => (string) Str::uuid(), 'bank_transaction_reference' => $bankReference, 'bank_statement_reference' => 'STATEMENT-S067', 'booked_at' => '2026-09-09', 'evidence_amount' => '1210.00', 'currency' => 'CZK', 'counterparty_name' => 'S067 counterparty', 'evidence_note' => 'Prepared matching evidence.'], (int) $organization->id, $actor);

        $bankEvidence = app(BankTransactionEvidenceService::class)->record(['idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence', 'source_reference' => $bankReference, 'bank_statement_reference' => 'STATEMENT-S067', 'direction' => 'credit', 'booked_at' => '2026-09-09', 'value_date' => '2026-09-09', 'amount' => '1210.00', 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER', 'counterparty_name' => 'S067 counterparty', 'counterparty_account_identifier' => 'CZ-COUNTERPARTY', 'variable_symbol' => '67001', 'message' => 'Vehicle service recharge', 'evidence_note' => 'Recorded external money movement.'], (int) $organization->id, $actor);

        $input = ['expected_handoff_revision' => 1, 'bank_transaction_evidence_public_id' => $bankEvidence['bank_transaction_evidence_public_id'], 'expected_bank_transaction_evidence_revision' => 1, 'idempotency_key' => (string) Str::uuid(), 'matched_amount' => '1210.00', 'effective_date' => '2026-09-09', 'reason' => 'Approved manual match to the prepared billing-document handoff.'];
        $service = app(VehicleCostAllocationBankMatchingExecutionService::class);
        $first = $service->execute($handoff['handoff_public_id'], $input, (int) $organization->id, $actor);
        $again = $service->execute($handoff['handoff_public_id'], $input, (int) $organization->id, $actor);

        self::assertSame($first['execution_public_id'], $again['execution_public_id']);
        self::assertSame('executed', $first['status']);
        self::assertSame('1210.00', $first['matched_amount']);
        self::assertTrue($first['bank_matching_performed']);
        self::assertFalse($first['payment_marked']);
        self::assertFalse($first['billing_document_modified']);
        self::assertFalse($first['deposit_offset_performed']);
        self::assertFalse($first['repair_fund_movement_performed']);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_execution_events', 1);
        self::assertDatabaseCount('bank_transaction_evidence_events', 1);
        self::assertDatabaseHas('billing_documents', ['status' => 'draft', 'gross_amount' => '1210.00']);
        self::assertDatabaseCount('financial_calculations', 0);
    }
}
