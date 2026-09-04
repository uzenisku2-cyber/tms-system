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
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibilityEvaluation;
use App\Modules\Fuel\Services\FuelTransactionSettlementEligibilityService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class FuelTransactionSettlementEligibilityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_fails_closed_then_snapshots_historical_policy_and_revision(): void
    {
        $organization = $this->organization('S053 master');
        $actor = $this->actor($organization);
        $driver = $this->driver();
        $assignment = $this->assignment($driver, $organization, $actor);
        $card = $this->card($organization, $actor);
        $transaction = $this->transaction($organization, $actor, $driver, $card);
        $immutable = $this->immutable($transaction);
        $service = app(FuelTransactionSettlementEligibilityService::class);

        $notResolved = $service->evaluate($transaction, (int) $organization->id, $actor, 0);
        self::assertSame('blocked', $notResolved['status']);
        self::assertSame('reconciliation_not_resolved', $notResolved['result_code']);
        self::assertSame(1, $notResolved['revision']);

        FuelTransactionReconciliation::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $organization->id,
            'fuel_transaction_id' => $transaction->id,
            'status' => FuelTransactionReconciliation::STATUS_RESOLVED,
            'result_code' => 'driver_day_confirmed',
            'effective_driver_id' => $driver->id,
            'driver_organization_assignment_id' => $assignment->id,
            'service_date' => '2026-09-04',
            'candidate_count' => 0,
            'revision' => 7,
            'evaluated_at' => now(),
            'resolved_at' => now(),
        ]);

        $missingPolicy = $service->evaluate($transaction, (int) $organization->id, $actor, 1);
        self::assertSame('blocked', $missingPolicy['status']);
        self::assertSame('settlement_policy_missing', $missingPolicy['result_code']);
        self::assertSame(7, $missingPolicy['reconciliation_revision']);

        $this->policy($organization, $actor, $card, '2026-08-01', '2026-08-31');
        $activePolicy = $this->policy($organization, $actor, $card, '2026-09-01', null);
        $eligible = $service->evaluate($transaction, (int) $organization->id, $actor, 2);
        self::assertSame('eligible', $eligible['status']);
        self::assertSame('eligible', $eligible['result_code']);
        self::assertSame(3, $eligible['revision']);
        self::assertSame(7, $eligible['reconciliation_revision']);
        self::assertSame('driver', $eligible['settlement_target']);
        self::assertSame((int) $driver->id, $eligible['target_driver_id']);
        self::assertNull($eligible['target_organization_id']);
        self::assertSame('net', $eligible['amount_basis']);
        self::assertSame('1100.000000', $eligible['base_amount']);
        self::assertSame('CZK', $eligible['currency']);
        self::assertSame((int) $activePolicy->id, (int) FuelTransactionSettlementEligibilityEvaluation::query()->latest('id')->firstOrFail()->fuel_card_settlement_policy_id);

        try {
            $service->evaluate($transaction, (int) $organization->id, $actor, 2);
            self::fail('A stale eligibility revision must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }

        $this->policy($organization, $actor, $card, '2026-09-04', null);
        $ambiguous = $service->evaluate($transaction, (int) $organization->id, $actor, 3);
        self::assertSame('blocked', $ambiguous['status']);
        self::assertSame('settlement_policy_ambiguous', $ambiguous['result_code']);
        self::assertSame(4, FuelTransactionSettlementEligibilityEvaluation::query()->count());
        self::assertSame($immutable, $this->immutable($transaction->refresh()));
        self::assertDatabaseCount('financial_calculations', 0);

        $other = $this->organization('S053 unrelated');
        try {
            $service->show($transaction, (int) $other->id);
            self::fail('Another organization must not read the transaction eligibility.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
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

    private function driver(): Driver
    {
        return Driver::query()->create(['user_id' => User::factory()->create()->id, 'first_name' => 'Jan', 'last_name' => 'Settlement', 'license_number' => 'S053-'.Str::uuid(), 'license_category' => 'B', 'active' => true]);
    }

    private function assignment(Driver $driver, Organization $organization, User $actor): DriverOrganizationAssignment
    {
        return DriverOrganizationAssignment::query()->create(['driver_id' => $driver->id, 'organization_id' => $organization->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);
    }

    private function card(Organization $organization, User $actor): FuelCard
    {
        return FuelCard::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_card_identifier' => 'S053-CARD', 'masked_card_number' => '**** 0053', 'status' => 'active', 'valid_from' => '2025-01-01', 'currency' => 'CZK', 'lock_version' => 1, 'created_by_user_id' => $actor->id]);
    }

    private function policy(Organization $organization, User $actor, FuelCard $card, string $from, ?string $until): FuelCardSettlementPolicy
    {
        return FuelCardSettlementPolicy::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'fuel_card_id' => $card->id, 'settlement_target' => 'driver', 'discount_beneficiary' => 'driver', 'amount_basis' => 'net', 'vat_mode' => 'not_applicable', 'valid_from' => $from, 'valid_until' => $until, 'reason' => 'S053 historical settlement contract.', 'created_by_user_id' => $actor->id]);
    }

    private function transaction(Organization $organization, User $actor, Driver $driver, FuelCard $card): FuelTransaction
    {
        $batch = FuelImportBatch::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 's053.csv', 'file_sha256' => str_repeat('5', 64), 'schema_fingerprint' => str_repeat('3', 64), 'source_row_count' => 1, 'accepted_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now()]);

        return FuelTransaction::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_transaction_identifier' => 'S053-001', 'transaction_fingerprint' => str_repeat('B', 64), 'occurred_at' => '2026-09-04 10:00:00', 'provider_card_identifier' => 'S053-CARD', 'fuel_card_id' => $card->id, 'responsible_organization_id' => $organization->id, 'driver_id' => $driver->id, 'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period', 'quantity' => '40.000000', 'unit_of_measure' => 'L', 'net_amount' => '1100.000000', 'tax_amount' => '231.000000', 'gross_amount' => '1331.000000', 'currency' => 'CZK', 'fuel_import_batch_id' => $batch->id, 'source_row' => 2]);
    }

    private function immutable(FuelTransaction $transaction): array
    {
        return array_replace($transaction->only(['provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'provider_card_identifier', 'fuel_card_id', 'responsible_organization_id', 'driver_id', 'quantity', 'net_amount', 'tax_amount', 'gross_amount', 'currency', 'fuel_import_batch_id', 'source_row']), ['occurred_at' => $transaction->getRawOriginal('occurred_at')]);
    }
}
