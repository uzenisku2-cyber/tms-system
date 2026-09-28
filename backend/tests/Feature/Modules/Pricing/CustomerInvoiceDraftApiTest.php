<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CustomerInvoiceDraftApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_detail_is_scoped_to_issuer_and_customer_and_exposes_exact_lines(): void
    {
        $issuer = $this->organization('Issuer');
        $customer = $this->organization('Customer');
        $foreign = $this->organization('Other');
        $actor = User::factory()->create();
        $this->grant($actor, $issuer, 'compensation.view');
        $document = BillingDocument::query()->create([
            'owner_organization_id' => $issuer->id,
            'counterparty_organization_id' => $customer->id,
            'document_type' => BillingDocument::TYPE_CUSTOMER_INVOICE,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30',
            'currency' => 'CZK', 'vat_treatment' => BillingDocument::VAT_STANDARD,
            'vat_status_snapshot' => 'payer', 'net_amount' => '100.00',
            'vat_rate' => '21.00', 'vat_amount' => '21.00', 'gross_amount' => '121.00',
            'status' => 'draft', 'source_snapshot' => [
                'issuer' => ['name' => 'Issuer'], 'customer' => ['name' => 'Customer'],
            ], 'created_by_user_id' => $actor->id,
        ]);
        BillingDocumentLine::query()->create([
            'billing_document_id' => $document->id, 'description' => 'Route service',
            'quantity' => '1.000', 'unit_rate' => '100.0000', 'net_amount' => '100.00',
            'vat_amount' => '21.00', 'gross_amount' => '121.00',
            'position' => 1, 'created_at' => now(),
        ]);
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $issuer->id)
            ->getJson('/api/v1/customer-invoices/'.$document->public_id)
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.document_number', null)
            ->assertJsonPath('data.lines.0.gross_amount', '121.00');

        $other = User::factory()->create();
        $this->grant($other, $foreign, 'compensation.view');
        Sanctum::actingAs($other);
        $this->withHeader('X-Organization-ID', (string) $foreign->id)
            ->getJson('/api/v1/customer-invoices/'.$document->public_id)
            ->assertNotFound();
    }

    public function test_draft_creation_requires_manage_permission_and_valid_input(): void
    {
        $issuer = $this->organization('Issuer');
        $actor = User::factory()->create();
        $this->grant($actor, $issuer, 'compensation.view');
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $issuer->id)
            ->postJson('/api/v1/customer-invoices', [])->assertForbidden();

        $this->grant($actor, $issuer, 'compensation.manage');
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $issuer->id)
            ->postJson('/api/v1/customer-invoices', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['idempotency_key', 'calculation_public_ids']);
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create([
            'name' => $name, 'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function grant(User $actor, Organization $organization, string $permissionName): void
    {
        OrganizationMembership::query()->firstOrCreate([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
        ], [
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(), 'valid_until' => null,
        ]);
        $registrar = app(PermissionRegistrar::class);
        $prior = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId((int) $organization->id);
            $registrar->forgetCachedPermissions();
            $actor->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($prior);
            $registrar->forgetCachedPermissions();
        }
    }
}
