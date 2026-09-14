<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidateEvent;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementBankMatchCandidateReviewLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_review_accepts_rejects_and_supersedes_idempotently_without_execution(): void
    {
        $organization = $this->organization('S079 review master', Organization::TYPE_MASTER);
        $carrier = $this->organization('S079 review carrier', Organization::TYPE_CARRIER);
        $actor = User::factory()->create();
        $this->authorizeAndAuthenticate($actor, $organization);

        foreach (['accepted', 'rejected', 'superseded'] as $index => $decision) {
            [$statement, $candidate, $document] = $this->proposedCandidate($organization, $carrier, $actor, $index + 1);
            $url = "/api/v1/financial-settlement-statements/{$statement->public_id}/bank-match-candidates/{$candidate->public_id}/review";
            $payload = [
                'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
                'decision' => $decision, 'reason' => "Human review decided {$decision}.",
            ];
            if ($decision === 'rejected') {
                $stale = $payload;
                $stale['idempotency_key'] = (string) Str::uuid();
                $stale['expected_revision'] = 2;
                $this->postJson($url, $stale)->assertUnprocessable()->assertJsonValidationErrors('expected_revision');
            }
            $response = $this->postJson($url, $payload)->assertOk()
                ->assertJsonPath('data.status', $decision)
                ->assertJsonPath('data.revision', 2)
                ->assertJsonPath('data.payment_allocation_created', false)
                ->assertJsonPath('data.payment_marked', false)
                ->assertJsonPath('data.bank_matching_performed', false);
            $this->postJson($url, $payload)->assertOk()
                ->assertJsonPath('data.public_id', $response->json('data.public_id'))
                ->assertJsonPath('data.status', $decision)
                ->assertJsonPath('data.revision', 2);
            self::assertSame(5, (int) $statement->fresh()->revision);
            self::assertSame('draft', $document->fresh()->status);
        }

        self::assertDatabaseCount('financial_settlement_bank_match_candidates', 3);
        self::assertDatabaseCount('financial_settlement_bank_match_candidate_events', 6);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
    }

    /** @return array{FinancialSettlementStatement, FinancialSettlementBankMatchCandidate, BillingDocument} */
    private function proposedCandidate(Organization $organization, Organization $carrier, User $actor, int $sequence): array
    {
        $amount = 100000 + ($sequence * 10000);
        $money = number_format($amount / 100, 2, '.', '');
        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'counterparty_organization_id' => $carrier->id, 'driver_id' => null,
            'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => $money, 'vat_rate' => null, 'vat_amount' => '0.00', 'gross_amount' => $money,
            'status' => 'draft', 'source_snapshot' => [], 'created_by_user_id' => $actor->id,
        ]);
        BillingDocumentCommercialIdentity::query()->create([
            'public_id' => (string) Str::uuid(), 'billing_document_id' => $document->id,
            'owner_organization_id' => $organization->id,
            'direction' => BillingDocumentCommercialIdentity::DIRECTION_PAYABLE,
            'document_number' => "SET-079-{$sequence}", 'variable_symbol' => "7900{$sequence}",
            'issued_on' => '2026-09-13', 'taxable_supply_on' => null, 'due_on' => '2026-09-20',
            'counterparty_name' => 'S079 review carrier', 'counterparty_registration_number' => null,
            'counterparty_vat_number' => null, 'counterparty_account_identifier' => '123456789/0100',
            'counterparty_snapshot' => [], 'revision' => 1, 'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', "identity-{$sequence}"), 'created_by_user_id' => $actor->id,
        ]);
        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->id, 'recipient_driver_id' => null,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'status' => FinancialSettlementStatement::STATUS_CLOSED, 'earning_amount_minor' => $amount,
            'deduction_amount_minor' => 0, 'net_balance_minor' => $amount, 'source_snapshot' => [],
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', "statement-{$sequence}"),
            'revision' => 5, 'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => BillingDocumentCommercialIdentity::DIRECTION_PAYABLE, 'output_materialized_at' => now(),
        ]);
        $evidence = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => "BANK-S079-REVIEW-{$sequence}", 'bank_statement_reference' => 'S079-REVIEW',
            'direction' => 'debit', 'booked_at' => '2026-09-18', 'value_date' => '2026-09-18',
            'amount' => $money, 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'S079 review carrier', 'counterparty_account_identifier' => '123456789/0100',
            'variable_symbol' => "7900{$sequence}", 'message' => 'Settlement review evidence',
            'evidence_note' => 'S079 review candidate source.', 'status' => 'recorded',
            'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $candidateFingerprint = hash('sha256', "S079-review-candidate-{$sequence}");
        $idempotencyKey = (string) Str::uuid();
        $matchReasons = [
            'amount_exact' => true, 'direction_exact' => true, 'currency_exact' => true,
            'variable_symbol_exact' => true, 'counterparty_account_exact' => true,
            'counterparty_name_exact' => true, 'date_within_window' => true, 'date_distance_days' => 2,
        ];
        $sourceSnapshot = [
            'command_fingerprint' => hash('sha256', "S079-review-command-{$sequence}"),
            'financial_settlement_statement_public_id' => $statement->public_id,
            'billing_document_public_id' => $document->public_id,
            'document_number' => "SET-079-{$sequence}", 'settlement_variable_symbol' => "7900{$sequence}",
            'settlement_counterparty_name' => 'S079 review carrier',
            'settlement_counterparty_account_identifier' => '123456789/0100',
            'settlement_due_on' => '2026-09-20', 'bank_transaction_evidence_public_id' => $evidence->public_id,
            'bank_source_reference' => "BANK-S079-REVIEW-{$sequence}", 'bank_variable_symbol' => "7900{$sequence}",
            'bank_counterparty_name' => 'S079 review carrier',
            'bank_counterparty_account_identifier' => '123456789/0100', 'bank_booked_at' => '2026-09-18',
            'minimum_score_basis_points' => 6000, 'date_window_days' => 7,
            'payment_allocation_created' => false, 'payment_marked' => false, 'bank_matching_performed' => false,
        ];
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => $idempotencyKey, 'candidate_fingerprint' => $candidateFingerprint,
            'currency' => 'CZK', 'expected_bank_direction' => 'debit', 'bank_amount_minor' => $amount,
            'settlement_outstanding_amount_minor' => $amount, 'proposed_amount_minor' => $amount,
            'score_basis_points' => 10000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_PROPOSED,
            'match_reasons' => $matchReasons, 'source_snapshot' => $sourceSnapshot, 'revision' => 1,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(),
        ]);
        FinancialSettlementBankMatchCandidateEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'financial_settlement_bank_match_candidate_id' => $candidate->id,
            'revision' => 1, 'event_type' => 'candidate_proposed', 'idempotency_key' => $idempotencyKey,
            'candidate_fingerprint' => $candidateFingerprint, 'reason' => 'Create candidate for review lifecycle.',
            'evidence' => ['match_reasons' => $matchReasons, 'source_snapshot' => $sourceSnapshot],
            'actor_user_id' => $actor->id, 'occurred_at' => now(),
        ]);

        return [$statement, $candidate, $document];
    }

    private function authorizeAndAuthenticate(User $actor, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        Sanctum::actingAs($actor, ['*'], 'web');
        $this->withHeader('X-Organization-ID', (string) $organization->id);
    }

    private function organization(string $name, string $type): Organization
    {
        return Organization::query()->create(['name' => $name, 'type' => $type, 'status' => Organization::STATUS_ACTIVE]);
    }
}
