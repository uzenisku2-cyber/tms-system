<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardSettlementPolicy;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplication;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplicationEvent;
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibility;
use App\Modules\Fuel\Services\FuelTransactionSettlementApplicationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FuelTransactionSettlementApplicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_snapshots_eligibility_prevents_duplicates_and_reverses_append_only(): void
    {
        $organization = Organization::query()->create(['name' => 'S054 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actor($organization);
        $driver = Driver::query()->create(['user_id' => User::factory()->create()->id, 'first_name' => 'Jan', 'last_name' => 'Application', 'license_number' => 'S054-'.Str::uuid(), 'license_category' => 'B', 'active' => true]);
        $assignment = DriverOrganizationAssignment::query()->create(['driver_id' => $driver->id, 'organization_id' => $organization->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);
        $card = FuelCard::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_card_identifier' => 'S054-CARD', 'masked_card_number' => '**** 0054', 'status' => 'active', 'valid_from' => '2025-01-01', 'currency' => 'CZK', 'lock_version' => 1, 'created_by_user_id' => $actor->id]);
        $policy = FuelCardSettlementPolicy::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'fuel_card_id' => $card->id, 'settlement_target' => 'driver', 'discount_beneficiary' => 'driver', 'amount_basis' => 'net', 'vat_mode' => 'not_applicable', 'valid_from' => '2026-09-01', 'reason' => 'S054 policy.', 'created_by_user_id' => $actor->id]);
        $batch = FuelImportBatch::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 's054.csv', 'file_sha256' => str_repeat('5', 64), 'schema_fingerprint' => str_repeat('4', 64), 'source_row_count' => 1, 'accepted_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now()]);
        $transaction = FuelTransaction::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_transaction_identifier' => 'S054-001', 'transaction_fingerprint' => str_repeat('C', 64), 'occurred_at' => '2026-09-04 10:00:00', 'provider_card_identifier' => 'S054-CARD', 'fuel_card_id' => $card->id, 'responsible_organization_id' => $organization->id, 'driver_id' => $driver->id, 'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period', 'quantity' => '40.000000', 'unit_of_measure' => 'L', 'net_amount' => '1200.000000', 'tax_amount' => '252.000000', 'gross_amount' => '1452.000000', 'currency' => 'CZK', 'fuel_import_batch_id' => $batch->id, 'source_row' => 2]);
        $reconciliation = FuelTransactionReconciliation::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'fuel_transaction_id' => $transaction->id, 'status' => FuelTransactionReconciliation::STATUS_RESOLVED, 'result_code' => 'driver_day_confirmed', 'effective_driver_id' => $driver->id, 'driver_organization_assignment_id' => $assignment->id, 'service_date' => '2026-09-04', 'candidate_count' => 0, 'revision' => 7, 'evaluated_at' => now(), 'resolved_at' => now()]);
        $eligibility = FuelTransactionSettlementEligibility::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'fuel_transaction_id' => $transaction->id, 'status' => FuelTransactionSettlementEligibility::STATUS_ELIGIBLE, 'result_code' => 'eligible', 'fuel_card_settlement_policy_id' => $policy->id, 'reconciliation_revision' => 7, 'settlement_target' => 'driver', 'target_driver_id' => $driver->id, 'discount_beneficiary' => 'driver', 'amount_basis' => 'net', 'vat_mode' => 'not_applicable', 'base_amount' => '1200.000000', 'currency' => 'CZK', 'revision' => 3, 'evaluated_at' => now()]);
        $service = app(FuelTransactionSettlementApplicationService::class);
        $applied = $service->apply($transaction, (int) $organization->id, $actor, 3);
        self::assertSame('applied', $applied['status']);
        self::assertSame(1, $applied['revision']);
        self::assertSame(3, $applied['eligibility_revision']);
        self::assertSame(7, $applied['reconciliation_revision']);
        self::assertSame('1200.000000', $applied['applied_amount']);
        self::assertNull($applied['financial_calculation_id']);
        self::assertDatabaseCount('financial_calculations', 0);
        self::assertDatabaseCount('fuel_transaction_settlement_application_events', 1);
        try {
            $service->apply($transaction, (int) $organization->id, $actor, 3);
            self::fail('Double settlement application must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('settlement_application', $exception->errors());
        }
        $reversed = $service->reverse($transaction, (int) $organization->id, $actor, 1, 'Incorrect settlement target confirmed by dispatcher.');
        self::assertSame('reversed', $reversed['status']);
        self::assertSame(2, $reversed['revision']);
        self::assertCount(2, $reversed['events']);
        self::assertSame('applied', $reversed['events'][0]['event_type']);
        self::assertSame('reversed', $reversed['events'][1]['event_type']);
        self::assertSame(1, FuelTransactionSettlementApplication::query()->count());
        self::assertSame(2, FuelTransactionSettlementApplicationEvent::query()->count());
        self::assertSame((int) $eligibility->id, (int) FuelTransactionSettlementApplication::query()->sole()->fuel_transaction_settlement_eligibility_id);
        try {
            $service->apply($transaction, (int) $organization->id, $actor, 3);
            self::fail('A reversed application must still prevent double settlement.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('settlement_application', $exception->errors());
        }
        self::assertSame('1200.000000', FuelTransactionSettlementApplication::query()->sole()->applied_amount);
        self::assertSame(7, (int) $reconciliation->revision);
    }

    private function actor(Organization $organization): User
    {
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

        return $actor;
    }
}
