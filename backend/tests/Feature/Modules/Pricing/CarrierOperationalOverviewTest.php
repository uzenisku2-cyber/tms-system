<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CarrierOperationalOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_carrier_manager_reads_own_historical_stats_without_general_financial_permission(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $other = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $manager = User::factory()->create();
        $driverUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->member($carrier, $manager);
        $this->member($carrier, $driverUser);
        $this->member($other, $otherUser);
        $this->seed(RolePermissionSeeder::class);
        $this->role($carrier, $manager, 'carrier-admin');
        $this->role($carrier, $driverUser, 'driver');
        $this->relationship($master, $carrier);
        $this->relationship($master, $other);
        $ownAssignment = $this->report($master, $carrier, $driverUser, 21);
        $otherAssignment = $this->report($master, $other, $otherUser, 900);
        $ownSettlement = $this->settlement($master, $carrier, $manager, 12345);
        $this->settlement($master, $other, $manager, 999999);
        $ownDocument = $this->document($master, $carrier, $manager, '123.45');
        $this->document($master, $other, $manager, '9999.99');
        $ownFuel = $this->fuel($master, $ownAssignment, $manager, '42.00');
        $this->fuel($master, $otherAssignment, $manager, '999.00');

        Sanctum::actingAs($manager);
        $headers = ['X-Organization-ID' => (string) $carrier->getKey()];
        $this->withHeaders($headers)->getJson('/api/v1/carrier/overview')
            ->assertOk()
            ->assertJsonPath('data.statistics.routes', 1)
            ->assertJsonPath('data.statistics.delivered', 21)
            ->assertJsonCount(1, 'data.settlements')
            ->assertJsonPath('data.settlements.0.public_id', $ownSettlement->public_id)
            ->assertJsonCount(1, 'data.billing_documents')
            ->assertJsonPath('data.billing_documents.0.public_id', $ownDocument->public_id)
            ->assertJsonCount(1, 'data.fuel_transactions')
            ->assertJsonPath('data.fuel_transactions.0.public_id', $ownFuel->public_id);
        $this->withHeaders($headers)->getJson('/api/v1/carrier/depot-route-status')
            ->assertOk()
            ->assertJsonPath('data.summary.awaiting_depot', 1)
            ->assertJsonPath('data.summary.matched_pending_approval', 0)
            ->assertJsonPath('data.approval_recorded', false)
            ->assertJsonCount(1, 'data.routes');
        $this->withHeaders($headers)->getJson('/api/v1/financial-settlement-statements')
            ->assertForbidden();
        Sanctum::actingAs($driverUser);
        $this->withHeaders($headers)->getJson('/api/v1/driver/depot-route-status')
            ->assertOk()
            ->assertJsonCount(1, 'data.routes')
            ->assertJsonPath('data.routes.0.status', 'awaiting_depot')
            ->assertJsonPath('data.routes.0.depot_values', null);
        $this->withHeaders($headers)->getJson('/api/v1/daily-reports/record-review/depot-driver/'.Str::uuid())
            ->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/carrier/overview')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/carrier/depot-route-status')->assertForbidden();
        Sanctum::actingAs($manager);
        $this->withHeaders(['X-Organization-ID' => (string) $other->getKey()])
            ->getJson('/api/v1/carrier/overview')->assertForbidden();
    }

    private function organization(string $type): Organization
    {
        return Organization::query()->create([
            'name' => 'Org '.fake()->uuid(), 'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $user): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
    }

    private function role(Organization $organization, User $user, string $name): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->getKey());
        $user->assignRole($name);
        $registrar->setPermissionsTeamId(null);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    private function relationship(Organization $master, Organization $carrier): void
    {
        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
    }

    private function report(Organization $master, Organization $carrier, User $user, int $delivered): DriverOrganizationAssignment
    {
        $driver = Driver::query()->create([
            'user_id' => $user->getKey(), 'first_name' => 'Driver',
            'last_name' => 'Test', 'active' => true,
        ]);
        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(), 'organization_id' => $carrier->getKey(),
            'created_by_user_id' => $user->getKey(), 'valid_from' => '2026-07-01',
        ]);
        DailyReport::query()->create([
            'organization_id' => $master->getKey(),
            'performed_by_driver_id' => $driver->getKey(),
            'entered_by_user_id' => $user->getKey(),
            'route_number' => 'R-'.fake()->uuid(),
            'route_number_normalized' => 'r-'.fake()->uuid(),
            'service_date' => '2026-07-29', 'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
            'entered_on_behalf' => true, 'delivered_parcels' => $delivered,
            'current_version' => 1,
        ]);

        return $assignment;
    }

    private function settlement(Organization $master, Organization $carrier, User $actor, int $amount): FinancialSettlementStatement
    {
        return FinancialSettlementStatement::query()->create([
            'owner_organization_id' => $master->getKey(),
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->getKey(),
            'period_from' => '2026-07-01', 'period_until' => '2026-07-31',
            'currency' => 'CZK', 'status' => FinancialSettlementStatement::STATUS_APPROVED,
            'earning_amount_minor' => $amount, 'deduction_amount_minor' => 0,
            'net_balance_minor' => $amount, 'source_snapshot' => [],
            'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => str_repeat('a', 64),
            'created_by_user_id' => $actor->getKey(),
        ]);
    }

    private function document(Organization $master, Organization $carrier, User $actor, string $amount): BillingDocument
    {
        return BillingDocument::query()->create([
            'owner_organization_id' => $master->getKey(),
            'counterparty_organization_id' => $carrier->getKey(),
            'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-07-01', 'period_until' => '2026-07-31',
            'currency' => 'CZK', 'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE,
            'vat_status_snapshot' => 'non_payer', 'net_amount' => $amount,
            'vat_amount' => 0, 'gross_amount' => $amount,
            'status' => 'approved', 'source_snapshot' => [],
            'created_by_user_id' => $actor->getKey(),
        ]);
    }

    private function fuel(Organization $master, DriverOrganizationAssignment $assignment, User $actor, string $amount): FuelTransaction
    {
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $master->getKey(),
            'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 'fuel.xlsx',
            'file_sha256' => hash('sha256', (string) Str::uuid()),
            'schema_fingerprint' => str_repeat('b', 64),
            'imported_by_user_id' => $actor->getKey(),
        ]);

        return FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $master->getKey(),
            'provider' => 'ORLEN', 'transaction_fingerprint' => hash('sha256', (string) Str::uuid()),
            'occurred_at' => '2026-07-29 12:00:00', 'provider_card_identifier' => 'CARD',
            'actual_driver_id' => $assignment->driver_id,
            'actual_driver_organization_assignment_id' => $assignment->getKey(),
            'match_status' => 'matched', 'quantity' => '10.000000',
            'unit_of_measure' => 'L', 'gross_amount' => $amount, 'currency' => 'CZK',
            'fuel_import_batch_id' => $batch->getKey(), 'source_row' => 1,
        ]);
    }
}
