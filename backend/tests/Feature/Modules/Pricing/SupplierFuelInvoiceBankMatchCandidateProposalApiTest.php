<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankMatchCandidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceBankMatchCandidateProposalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_proposal_is_explainable_idempotent_scoped_and_non_executing(): void
    {
        $organization = $this->organization('S077 master');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        $invoice = $this->invoice('ORLEN-MATCH-001', '77001', '1210.00');
        $this->evidence($organization, $actor, 'BANK-S077-001', '77001', '1210.00');
        $url = "/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates";
        $payload = [
            'idempotency_key' => '5594de2e-ab48-4d30-b5e1-944b0108e256',
            'minimum_score_basis_points' => 6000, 'date_window_days' => 31,
            'reason' => 'Propose a reviewable supplier payment match.',
        ];

        $created = $this->postJson($url, $payload)->assertCreated()
            ->assertJsonPath('data.status', 'proposed')
            ->assertJsonPath('data.score_basis_points', 10000)
            ->assertJsonPath('data.proposed_amount_minor', 121000)
            ->assertJsonPath('data.match_reasons.amount_exact', true)
            ->assertJsonPath('data.match_reasons.variable_symbol_exact', true)
            ->assertJsonPath('data.match_reasons.counterparty_account_exact', true)
            ->assertJsonPath('data.match_reasons.supplier_name_exact', true)
            ->assertJsonPath('data.payment_created', false)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.matching_execution_created', false);
        $this->postJson($url, $payload)->assertOk()
            ->assertJsonPath('data.public_id', $created->json('data.public_id'));
        $this->getJson($url)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $created->json('data.public_id'))
            ->assertJsonPath('data.0.status', 'proposed')
            ->assertJsonPath('data.0.score_basis_points', 10000)
            ->assertJsonPath('data.0.payment_created', false);

        $changed = $payload;
        $changed['minimum_score_basis_points'] = 7000;
        $this->postJson($url, $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        $foreign = $this->organization('S077 foreign');
        $foreignActor = User::factory()->create();
        $this->authorizeActor($foreignActor, $foreign);
        $this->authenticate($foreignActor, $foreign);
        $this->postJson($url, [
            'idempotency_key' => (string) Str::uuid(), 'reason' => 'Cross organization proposal.',
        ])->assertNotFound();
        $this->getJson($url)->assertNotFound();

        self::assertDatabaseCount('supplier_fuel_invoice_bank_match_candidates', 1);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_match_candidate_events', 1);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertSame(SupplierFuelInvoiceBankMatchCandidate::STATUS_PROPOSED, SupplierFuelInvoiceBankMatchCandidate::query()->sole()->status);
    }

    private function invoice(string $number, string $variableSymbol, string $gross): string
    {
        $minor = (int) str_replace('.', '', $gross);
        $netMinor = (int) round($minor / 1.21);
        $vatMinor = $minor - $netMinor;
        $money = static fn (int $value): string => intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
        $response = $this->postJson('/api/v1/supplier-fuel-invoices', [
            'idempotency_key' => (string) Str::uuid(), 'document_number' => $number,
            'variable_symbol' => $variableSymbol, 'issued_on' => '2026-09-11',
            'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-25',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_account_identifier' => '123456789/0100', 'currency' => 'CZK',
            'description' => 'S077 supplier fuel invoice', 'net_amount' => $money($netMinor),
            'vat_rate_basis_points' => 2100, 'vat_amount' => $money($vatMinor), 'gross_amount' => $gross,
        ])->assertCreated();

        return (string) $response->json('data.public_id');
    }

    private function evidence(Organization $organization, User $actor, string $reference, string $variableSymbol, string $amount): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->unsetRelation('permissions');
        app(BankTransactionEvidenceService::class)->record([
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => $reference, 'bank_statement_reference' => 'S077-STATEMENT',
            'direction' => 'debit', 'booked_at' => '2026-09-12', 'value_date' => '2026-09-12',
            'amount' => $amount, 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_account_identifier' => '123456789/0100', 'variable_symbol' => $variableSymbol,
            'message' => 'Supplier fuel invoice payment', 'evidence_note' => 'S077 matching evidence.',
        ], (int) $organization->id, $actor);
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
        $user->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
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
