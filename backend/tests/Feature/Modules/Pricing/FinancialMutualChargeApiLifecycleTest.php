<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialMutualChargeApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_charge_is_exact_idempotent_shared_disputable_reversible_and_non_executing(): void
    {
        $owner = $this->organization('Master carrier');
        $counterparty = $this->organization('External carrier');
        $ownerActor = User::factory()->create();
        $counterpartyActor = User::factory()->create();
        $this->authorize($ownerActor, $owner);
        $this->authorize($counterpartyActor, $counterparty);
        $this->authenticate($ownerActor, $owner);

        $payload = [
            'idempotency_key' => '157e87b9-c76d-4f31-9938-b5208ad014ae',
            'counterparty_type' => 'organization',
            'counterparty_organization_id' => $counterparty->id,
            'counterparty_driver_id' => null,
            'direction' => 'receivable',
            'category' => 'fuel',
            'description' => 'Fuel rebilling for August 2026.',
            'service_period_from' => '2026-08-01',
            'service_period_until' => '2026-08-31',
            'amount_minor' => 125000,
            'currency' => 'czk',
            'vat_treatment' => 'standard',
            'offset_eligible' => true,
            'source_type' => 'fuel_transaction_settlement_application',
            'source_public_id' => 'd4aa3c08-04a1-4fc7-9c81-32a68af78c71',
            'source_snapshot' => ['purchase_cost_minor' => 100000, 'rebilled_amount_minor' => 125000, 'margin_minor' => 25000],
        ];

        $created = $this->postJson('/api/v1/financial-mutual-charges', $payload);
        $created->assertCreated()
            ->assertJsonPath('data.amount_minor', 125000)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.visibility_status', 'private')
            ->assertJsonPath('data.source_snapshot.margin_minor', 25000)
            ->assertJsonPath('data.billing_document_created', false)
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false);
        $publicId = (string) $created->json('data.public_id');
        $this->postJson('/api/v1/financial-mutual-charges', $payload)->assertOk()->assertJsonPath('data.public_id', $publicId);
        $changed = $payload;
        $changed['amount_minor'] = 125001;
        $this->postJson('/api/v1/financial-mutual-charges', $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        $confirm = ['idempotency_key' => 'e0e85588-5d12-44b5-a290-f89a3cda55dc', 'expected_revision' => 1, 'reason' => 'Approved for sharing.'];
        $this->postJson("/api/v1/financial-mutual-charges/{$publicId}/confirm", $confirm)
            ->assertCreated()->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.visibility_status', 'shared')->assertJsonPath('data.revision', 2);
        $this->postJson("/api/v1/financial-mutual-charges/{$publicId}/confirm", $confirm)->assertOk()->assertJsonPath('data.revision', 2);

        $this->authenticate($counterpartyActor, $counterparty);
        $dispute = ['idempotency_key' => '4cd1694b-9daa-4b7b-bfab-8889e32d65ac', 'expected_revision' => 2, 'reason' => 'Counterparty requests source review.'];
        $this->postJson("/api/v1/financial-mutual-charges/{$publicId}/dispute", $dispute)
            ->assertCreated()->assertJsonPath('data.status', 'disputed')->assertJsonPath('data.revision', 3);

        $this->authenticate($ownerActor, $owner);
        $reverse = ['idempotency_key' => '2816db33-137e-4364-9510-763d803b98c8', 'expected_revision' => 3, 'reason' => 'Charge replaced by corrected evidence.'];
        $this->postJson("/api/v1/financial-mutual-charges/{$publicId}/reverse", $reverse)
            ->assertCreated()->assertJsonPath('data.status', 'reversed')->assertJsonPath('data.revision', 4);

        self::assertDatabaseCount('financial_mutual_charges', 1);
        self::assertDatabaseCount('financial_mutual_charge_events', 4);
        self::assertSame(125000, (int) FinancialMutualCharge::query()->sole()->amount_minor);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
        self::assertDatabaseCount('fuel_transaction_settlement_applications', 0);
    }

    private function authenticate(User $user, Organization $organization): void
    {
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $organization->id);
    }

    private function authorize(User $user, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(), 'valid_until' => null,
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
        return Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
    }
}
