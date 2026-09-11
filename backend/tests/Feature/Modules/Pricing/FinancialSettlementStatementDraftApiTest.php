<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementStatementDraftApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_draft_aggregates_confirmed_mutual_charges_with_signed_exact_balance_without_execution(): void
    {
        $owner = Organization::query()->create(['name' => 'Master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'Carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $owner->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $owner->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $owner->id);
        $charge = FinancialMutualCharge::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'counterparty_type' => 'organization', 'counterparty_organization_id' => $carrier->id,
            'direction' => 'receivable', 'category' => 'fuel', 'description' => 'Fuel rebilling.',
            'service_period_from' => '2026-08-01', 'service_period_until' => '2026-08-31',
            'amount_minor' => 125000, 'currency' => 'CZK', 'vat_treatment' => 'standard',
            'offset_eligible' => true, 'status' => 'confirmed', 'visibility_status' => 'shared',
            'source_type' => 'fuel', 'source_public_id' => (string) Str::uuid(),
            'source_snapshot' => ['purchase_cost_minor' => 100000, 'margin_minor' => 25000],
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => str_repeat('a', 64),
            'revision' => 2, 'created_by_user_id' => $actor->id, 'confirmed_by_user_id' => $actor->id,
            'confirmed_at' => now(), 'shared_at' => now(),
        ]);
        $payload = [
            'idempotency_key' => '344691d5-d35a-4dd5-9ccc-a5e54825f59d',
            'recipient_type' => 'organization', 'recipient_organization_id' => $carrier->id,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'financial_calculation_public_ids' => [], 'financial_mutual_charge_public_ids' => [$charge->public_id],
            'reason' => 'August settlement draft.',
        ];
        $created = $this->postJson('/api/v1/financial-settlement-statements', $payload);
        $created->assertCreated()->assertJsonPath('data.earning_amount_minor', 0)
            ->assertJsonPath('data.deduction_amount_minor', 125000)->assertJsonPath('data.net_balance_minor', -125000)
            ->assertJsonPath('data.lines.0.source_snapshot.source.margin_minor', 25000)
            ->assertJsonPath('data.billing_document_created', false)->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.bank_matching_performed', false);
        $this->postJson('/api/v1/financial-settlement-statements', $payload)->assertOk()->assertJsonPath('data.public_id', $created->json('data.public_id'));
        self::assertDatabaseCount('financial_settlement_statements', 1);
        self::assertDatabaseCount('financial_settlement_statement_lines', 1);
        self::assertDatabaseCount('financial_settlement_statement_events', 1);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
    }
}
