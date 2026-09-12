<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceBankMatchCandidateReviewLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_review_accepts_rejects_and_supersedes_idempotently_without_payment_execution(): void
    {
        $organization = $this->organization('S077 review master');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        $reviewed = [];

        foreach (['accepted', 'rejected', 'superseded'] as $index => $decision) {
            [$invoice, $candidate] = $this->proposedCandidate($organization, $actor, $index + 1);
            $url = "/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/review";
            $payload = [
                'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
                'decision' => $decision, 'reason' => "Human review decided {$decision}.",
            ];
            if ($decision === 'rejected') {
                $stale = $payload;
                $stale['expected_revision'] = 2;
                $this->postJson($url, $stale)->assertUnprocessable()->assertJsonValidationErrors('expected_revision');
            }
            $response = $this->postJson($url, $payload)->assertOk()
                ->assertJsonPath('data.status', $decision)
                ->assertJsonPath('data.revision', 2)
                ->assertJsonPath('data.payment_created', false)
                ->assertJsonPath('data.payment_marked', false)
                ->assertJsonPath('data.matching_execution_created', false)
                ->assertJsonCount(2, 'data.events');
            $this->postJson($url, $payload)->assertOk()
                ->assertJsonPath('data.public_id', $response->json('data.public_id'));
            if ($decision === 'accepted') {
                $changed = $payload;
                $changed['reason'] = 'Changed review command.';
                $this->postJson($url, $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
            }
            $reviewed[] = [$invoice, $candidate];
        }

        $foreign = $this->organization('S077 review foreign');
        $foreignActor = User::factory()->create();
        $this->authorizeActor($foreignActor, $foreign);
        $this->authenticate($foreignActor, $foreign);
        [$invoice, $candidate] = $reviewed[0];
        $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/review", [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2,
            'decision' => 'rejected', 'reason' => 'Cross organization review.',
        ])->assertNotFound();

        self::assertDatabaseCount('supplier_fuel_invoice_bank_match_candidates', 3);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_match_candidate_events', 6);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
    }

    public function test_accepted_candidate_materializes_one_exact_payment_idempotently(): void
    {
        $organization = $this->organization('S077 materialization master');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        [$invoice, $candidate] = $this->proposedCandidate($organization, $actor, 8);
        $reviewUrl = "/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/review";
        $this->postJson($reviewUrl, [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
            'decision' => 'accepted', 'reason' => 'Accepted before payment materialization.',
        ])->assertOk()->assertJsonPath('data.payment_created', false);

        $url = "/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/materialize";
        $payload = [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2,
            'reason' => 'Materialize accepted exact bank match.',
        ];
        $stale = $payload;
        $stale['expected_revision'] = 3;
        $this->postJson($url, $stale)->assertUnprocessable()->assertJsonValidationErrors('expected_revision');

        $response = $this->postJson($url, $payload)->assertCreated()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.revision', 3)
            ->assertJsonPath('data.payment_created', true)
            ->assertJsonPath('data.payment_marked', true)
            ->assertJsonPath('data.matching_execution_created', false)
            ->assertJsonPath('data.fuel_rebilling_mutated', false)
            ->assertJsonCount(3, 'data.events');
        $paymentPublicId = (string) $response->json('data.supplier_fuel_invoice_bank_payment_public_id');
        self::assertNotSame('', $paymentPublicId);
        $this->postJson($url, $payload)->assertOk()
            ->assertJsonPath('data.supplier_fuel_invoice_bank_payment_public_id', $paymentPublicId);
        $changed = $payload;
        $changed['reason'] = 'Changed materialization command.';
        $this->postJson($url, $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 1);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payment_events', 1);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_match_candidate_events', 3);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
    }

    public function test_rejected_candidate_cannot_be_materialized(): void
    {
        $organization = $this->organization('S077 rejected materialization');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        [$invoice, $candidate] = $this->proposedCandidate($organization, $actor, 9);
        $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/review", [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
            'decision' => 'rejected', 'reason' => 'Rejected before materialization.',
        ])->assertOk();
        $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates/{$candidate}/materialize", [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2,
            'reason' => 'Forbidden rejected candidate materialization.',
        ])->assertUnprocessable()->assertJsonValidationErrors('candidate');
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
    }

    /** @return array{string, string} */
    private function proposedCandidate(Organization $organization, User $actor, int $sequence): array
    {
        $variableSymbol = '7710'.$sequence;
        $invoice = $this->invoice('ORLEN-REVIEW-00'.$sequence, $variableSymbol, '99.99');
        $this->evidence($organization, $actor, 'BANK-S077-REVIEW-00'.$sequence, $variableSymbol, '99.99');
        $response = $this->postJson("/api/v1/supplier-fuel-invoices/{$invoice}/bank-match-candidates", [
            'idempotency_key' => (string) Str::uuid(), 'minimum_score_basis_points' => 6000,
            'date_window_days' => 31, 'reason' => 'Create candidate for human review.',
        ])->assertCreated();

        return [$invoice, (string) $response->json('data.public_id')];
    }

    private function invoice(string $number, string $variableSymbol, string $gross): string
    {
        $minor = (int) str_replace('.', '', $gross);
        $netMinor = (int) round($minor / 1.21);
        $vatMinor = $minor - $netMinor;
        $money = static fn (int $value): string => intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
        $response = $this->postJson('/api/v1/supplier-fuel-invoices', [
            'idempotency_key' => (string) Str::uuid(), 'document_number' => $number,
            'variable_symbol' => $variableSymbol, 'issued_on' => '2026-09-12',
            'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-25',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_account_identifier' => '123456789/0100', 'currency' => 'CZK',
            'description' => 'S077 review invoice', 'net_amount' => $money($netMinor),
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
            'source_reference' => $reference, 'bank_statement_reference' => 'S077-REVIEW',
            'direction' => 'debit', 'booked_at' => '2026-09-12', 'value_date' => '2026-09-12',
            'amount' => $amount, 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_account_identifier' => '123456789/0100', 'variable_symbol' => $variableSymbol,
            'message' => 'Supplier invoice review', 'evidence_note' => 'S077 review evidence.',
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
