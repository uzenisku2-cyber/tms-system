<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_draft_is_scoped_exact_idempotent_audited_and_non_executing(): void
    {
        $organization = $this->organization('Master carrier');
        $actor = User::factory()->create();
        $this->authorize($actor, $organization);
        $this->authenticate($actor, $organization);

        $payload = [
            'idempotency_key' => 'c5548b0e-b3d2-4cc0-ae62-734884f943ac',
            'document_number' => 'ORLEN-2026-0901', 'variable_symbol' => '20260901',
            'issued_on' => '2026-09-09', 'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-23',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_registration_number' => '27597075', 'counterparty_vat_number' => 'CZ27597075',
            'counterparty_account_identifier' => '123456789/0100', 'currency' => 'CZK',
            'description' => 'PHM za srpen 2026', 'net_amount' => '1000.00',
            'vat_rate_basis_points' => 2100, 'vat_amount' => '210.00', 'gross_amount' => '1210.00',
        ];

        $first = $this->postJson('/api/v1/supplier-fuel-invoices', $payload);
        $first->assertCreated()
            ->assertJsonPath('data.direction', 'payable')
            ->assertJsonPath('data.document_number', 'ORLEN-2026-0901')
            ->assertJsonPath('data.variable_symbol', '20260901')
            ->assertJsonPath('data.billing_document.document_type', 'supplier_fuel_invoice')
            ->assertJsonPath('data.billing_document.status', 'draft')
            ->assertJsonPath('data.billing_document.net_amount', '1000.00')
            ->assertJsonPath('data.billing_document.vat_amount', '210.00')
            ->assertJsonPath('data.billing_document.gross_amount', '1210.00')
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.fuel_transaction_allocation_performed', false);

        $replay = $this->postJson('/api/v1/supplier-fuel-invoices', $payload);
        $replay->assertOk()->assertJsonPath('data.public_id', $first->json('data.public_id'));

        self::assertDatabaseCount('billing_documents', 1);
        self::assertDatabaseCount('billing_document_lines', 1);
        self::assertDatabaseCount('billing_document_commercial_identities', 1);
        self::assertDatabaseCount('billing_document_commercial_identity_events', 1);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('fuel_transactions', 0);

        $document = BillingDocument::query()->sole();
        self::assertSame($organization->getKey(), $document->getAttribute('owner_organization_id'));
        self::assertSame(BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE, $document->getAttribute('document_type'));
        $identity = BillingDocumentCommercialIdentity::query()->sole();
        self::assertSame(BillingDocumentCommercialIdentity::DIRECTION_PAYABLE, $identity->getAttribute('direction'));
        $snapshot = $identity->getAttribute('counterparty_snapshot');
        self::assertIsArray($snapshot);
        self::assertSame('ORLEN Unipetrol RPA s.r.o.', $snapshot['name']);

        $changed = $payload;
        $changed['gross_amount'] = '1211.00';
        $this->postJson('/api/v1/supplier-fuel-invoices', $changed)
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        $duplicateNumber = $payload;
        $duplicateNumber['idempotency_key'] = '87fa8ef1-5d42-4c15-a0f0-6628a89acbb5';
        $this->postJson('/api/v1/supplier-fuel-invoices', $duplicateNumber)
            ->assertUnprocessable()->assertJsonValidationErrors('document_number');

        $invalidVat = $payload;
        $invalidVat['idempotency_key'] = '479cc073-7a68-4668-a36b-8fbe568b79b5';
        $invalidVat['document_number'] = 'ORLEN-2026-0902';
        $invalidVat['vat_amount'] = '211.00';
        $invalidVat['gross_amount'] = '1211.00';
        $this->postJson('/api/v1/supplier-fuel-invoices', $invalidVat)
            ->assertUnprocessable()->assertJsonValidationErrors('vat_amount');

        self::assertDatabaseCount('billing_documents', 1);
    }

    private function authenticate(User $user, Organization $organization): void
    {
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $organization->getKey());
    }

    private function authorize(User $user, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(), 'valid_until' => null,
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->getKey());
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
