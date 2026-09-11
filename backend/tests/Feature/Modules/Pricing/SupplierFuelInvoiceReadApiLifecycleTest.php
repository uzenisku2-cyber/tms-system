<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceReadApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_index_and_detail_are_scoped_exact_and_include_reversed_allocation_history(): void
    {
        $organization = $this->organization('Master carrier');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        $invoice = $this->invoice('ORLEN-READ-001');
        $this->invoice('MOL-READ-002');
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 's074.csv',
            'file_sha256' => str_repeat('7', 64), 'schema_fingerprint' => str_repeat('8', 64),
            'source_row_count' => 1, 'accepted_row_count' => 1,
            'imported_by_user_id' => $actor->id, 'completed_at' => now(),
        ]);
        $transaction = FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'provider_transaction_identifier' => 'S074-001',
            'provider_card_identifier' => 'S074-CARD', 'transaction_fingerprint' => hash('sha256', 'S074-001'),
            'occurred_at' => '2026-09-01 12:00:00', 'match_status' => 'matched',
            'match_method' => 'provider_card_and_assignment_period', 'quantity' => '10.000000',
            'unit_of_measure' => 'L', 'gross_amount' => '600.000000', 'currency' => 'CZK',
            'fuel_import_batch_id' => $batch->id, 'source_row' => 2,
        ]);

        $allocation = $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/fuel-transaction-allocations", [
            'idempotency_key' => (string) Str::uuid(),
            'fuel_transaction_public_id' => $transaction->public_id,
            'allocated_amount' => '600.00', 'reason' => 'Invoice read API allocation.',
        ])->assertCreated();

        $this->getJson('/api/v1/supplier-fuel-invoices?search=ORLEN&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.public_id', $invoice)
            ->assertJsonPath('data.items.0.allocation_summary.state', 'partially_allocated')
            ->assertJsonPath('data.items.0.allocation_summary.active_allocated_amount_minor', 60000)
            ->assertJsonPath('data.items.0.allocation_summary.unallocated_amount_minor', 61000)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false);

        $this->getJson("/api/v1/supplier-fuel-invoices/{$invoice}")
            ->assertOk()
            ->assertJsonCount(1, 'data.allocations')
            ->assertJsonPath('data.allocations.0.fuel_transaction.public_id', $transaction->public_id)
            ->assertJsonPath('data.allocations.0.status', 'active')
            ->assertJsonPath('data.fuel_transaction_allocation_performed', true);

        $allocationPublicId = (string) $allocation->json('data.public_id');
        $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/fuel-transaction-allocations/{$allocationPublicId}/reverse", [
            'expected_revision' => 1, 'reason' => 'Read API reversal verification.',
        ])->assertOk();

        $this->getJson("/api/v1/supplier-fuel-invoices/{$invoice}")
            ->assertOk()
            ->assertJsonCount(1, 'data.allocations')
            ->assertJsonPath('data.allocations.0.status', 'reversed')
            ->assertJsonPath('data.allocations.0.revision', 2)
            ->assertJsonCount(2, 'data.allocations.0.events')
            ->assertJsonPath('data.allocation_summary.state', 'unallocated')
            ->assertJsonPath('data.allocation_summary.active_allocated_amount_minor', 0)
            ->assertJsonPath('data.allocation_summary.unallocated_amount_minor', 121000)
            ->assertJsonPath('data.fuel_transaction_allocation_performed', false)
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false);

        $foreign = $this->organization('Foreign carrier');
        $foreignActor = User::factory()->create();
        $this->authorizeActor($foreignActor, $foreign);
        $this->authenticate($foreignActor, $foreign);
        $this->getJson("/api/v1/supplier-fuel-invoices/{$invoice}")->assertNotFound();
        $this->getJson('/api/v1/supplier-fuel-invoices')->assertOk()->assertJsonCount(0, 'data.items');

        self::assertDatabaseCount('bank_transaction_evidence', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('fuel_transaction_settlement_applications', 0);
    }

    private function invoice(string $number): string
    {
        $response = $this->postJson('/api/v1/supplier-fuel-invoices', [
            'idempotency_key' => (string) Str::uuid(), 'document_number' => $number,
            'issued_on' => '2026-09-09', 'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-23',
            'counterparty_name' => str_starts_with($number, 'ORLEN') ? 'ORLEN Unipetrol RPA s.r.o.' : 'MOL Ceska republika, s.r.o.',
            'currency' => 'CZK', 'description' => 'PHM read API test', 'net_amount' => '1000.00',
            'vat_rate_basis_points' => 2100, 'vat_amount' => '210.00', 'gross_amount' => '1210.00',
        ])->assertCreated();

        return (string) $response->json('data.public_id');
    }

    private function authenticate(User $user, Organization $organization): void
    {
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $organization->id);
    }

    private function authorizeActor(User $user, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $user->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $user->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create([
            'name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
