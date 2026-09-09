<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceTransactionAllocationApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_allocation_is_partial_exact_idempotent_scoped_and_non_executing(): void
    {
        $organization = $this->organization('Master carrier');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        $invoice = $this->invoice('ORLEN-ALLOC-001', '1000.00', '210.00', '1210.00');
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 's073.csv',
            'file_sha256' => str_repeat('7', 64), 'schema_fingerprint' => str_repeat('8', 64),
            'source_row_count' => 2, 'accepted_row_count' => 2,
            'imported_by_user_id' => $actor->id, 'completed_at' => now(),
        ]);
        $first = $this->transaction($organization, $batch, 'S073-001', '600.000000', 2);
        $second = $this->transaction($organization, $batch, 'S073-002', '610.000000', 3);
        $url = "/api/v1/supplier-fuel-invoices/{$invoice}/fuel-transaction-allocations";
        $payload = [
            'idempotency_key' => '34be66c0-7604-4cd9-903e-7c8bb9fa03c8',
            'fuel_transaction_public_id' => $first->public_id,
            'allocated_amount' => '600.00', 'reason' => 'First allocation.',
        ];

        $created = $this->postJson($url, $payload);
        $created->assertCreated()
            ->assertJsonPath('data.invoice_allocation_state', 'partially_allocated')
            ->assertJsonPath('data.invoice_unallocated_amount_minor', 61000)
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.fuel_settlement_mutated', false);
        $this->postJson($url, $payload)->assertOk()
            ->assertJsonPath('data.public_id', $created->json('data.public_id'));
        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocations', 1);
        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocation_events', 1);

        $changed = $payload;
        $changed['allocated_amount'] = '599.00';
        $this->postJson($url, $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        $overflow = [
            'idempotency_key' => '61082058-4ff1-4b78-a10e-08cc4b1ff547',
            'fuel_transaction_public_id' => $second->public_id,
            'allocated_amount' => '610.01', 'reason' => 'Invoice overflow.',
        ];
        $this->postJson($url, $overflow)->assertUnprocessable()->assertJsonValidationErrors('allocated_amount');
        $overflow['idempotency_key'] = 'edce634e-af41-4385-8476-aef6d0ac9cd2';
        $overflow['allocated_amount'] = '610.00';
        $this->postJson($url, $overflow)->assertCreated()
            ->assertJsonPath('data.invoice_allocation_state', 'fully_allocated')
            ->assertJsonPath('data.invoice_unallocated_amount_minor', 0);

        $otherInvoice = $this->invoice('ORLEN-ALLOC-002', '100.00', '21.00', '121.00');
        $this->postJson("/api/v1/supplier-fuel-invoices/{$otherInvoice}/fuel-transaction-allocations", [
            'idempotency_key' => 'c72833fe-9a82-48b3-b1de-fdd0c248de64',
            'fuel_transaction_public_id' => $first->public_id,
            'allocated_amount' => '1.00', 'reason' => 'Transaction overflow.',
        ])->assertUnprocessable()->assertJsonValidationErrors('allocated_amount');

        $foreignOrganization = $this->organization('Foreign carrier');
        $foreignActor = User::factory()->create();
        $this->authorizeActor($foreignActor, $foreignOrganization);
        $this->authenticate($foreignActor, $foreignOrganization);
        $this->postJson($url, [
            'idempotency_key' => 'fc3ff302-5fbc-4c58-b781-76d973a60866',
            'fuel_transaction_public_id' => $first->public_id,
            'allocated_amount' => '1.00', 'reason' => 'Cross organization.',
        ])->assertNotFound();

        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocations', 2);
        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocation_events', 2);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('fuel_transaction_settlement_applications', 0);

        $this->authenticate($actor, $organization);
        $allocation = SupplierFuelInvoiceTransactionAllocation::query()
            ->where('fuel_transaction_id', $second->id)
            ->sole();
        $reverseUrl = $url.'/'.$allocation->public_id.'/reverse';
        $this->postJson($reverseUrl, [
            'expected_revision' => 1,
            'reason' => 'Incorrect supplier invoice allocation.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'reversed')
            ->assertJsonPath('data.revision', 2)
            ->assertJsonPath('data.invoice_allocation_state', 'partially_allocated')
            ->assertJsonPath('data.invoice_unallocated_amount_minor', 61000)
            ->assertJsonCount(2, 'data.events');

        $this->postJson($reverseUrl, [
            'expected_revision' => 1,
            'reason' => 'Repeated stale reversal.',
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_revision');
        $this->postJson($reverseUrl, [
            'expected_revision' => 2,
            'reason' => 'Repeated current reversal.',
        ])->assertUnprocessable()->assertJsonValidationErrors('allocation');
        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocations', 2);
        self::assertDatabaseCount('supplier_fuel_invoice_transaction_allocation_events', 3);
    }

    private function invoice(string $number, string $net, string $vat, string $gross): string
    {
        $response = $this->postJson('/api/v1/supplier-fuel-invoices', [
            'idempotency_key' => (string) Str::uuid(), 'document_number' => $number,
            'issued_on' => '2026-09-09', 'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-23',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.', 'currency' => 'CZK',
            'description' => 'PHM allocation test', 'net_amount' => $net,
            'vat_rate_basis_points' => 2100, 'vat_amount' => $vat, 'gross_amount' => $gross,
        ])->assertCreated();

        return (string) $response->json('data.public_id');
    }

    private function transaction(Organization $organization, FuelImportBatch $batch, string $identifier, string $gross, int $row): FuelTransaction
    {
        return FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'provider_transaction_identifier' => $identifier,
            'provider_card_identifier' => 'S073-CARD',
            'transaction_fingerprint' => hash('sha256', $identifier), 'occurred_at' => '2026-08-31 12:00:00',
            'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period',
            'quantity' => '1.000000', 'unit_of_measure' => 'L', 'gross_amount' => $gross,
            'currency' => 'CZK', 'fuel_import_batch_id' => $batch->id, 'source_row' => $row,
        ]);
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
