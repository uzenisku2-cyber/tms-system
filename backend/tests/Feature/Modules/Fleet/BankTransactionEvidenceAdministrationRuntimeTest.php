<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionAmountBreakdown;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Models\BankTransactionEvidenceEvent;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BankTransactionEvidenceAdministrationRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_index_and_detail_are_scoped_capacity_aware_linked_audited_and_non_mutating(): void
    {
        $organization = Organization::query()->create(['name' => 'S086 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'S086 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S086 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        Sanctum::actingAs($actor);

        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'counterparty_organization_id' => $carrier->id,
            'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => '1000.00', 'vat_amount' => '0.00', 'gross_amount' => '1000.00',
            'status' => 'draft', 'source_snapshot' => [], 'created_by_user_id' => $actor->id,
        ]);
        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->id, 'period_from' => '2026-09-01', 'period_until' => '2026-09-30',
            'currency' => 'CZK', 'status' => FinancialSettlementStatement::STATUS_CLOSED,
            'earning_amount_minor' => 100000, 'deduction_amount_minor' => 0, 'net_balance_minor' => 100000,
            'source_snapshot' => [], 'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', 's086-statement'), 'revision' => 5,
            'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => 'payable', 'output_materialized_at' => now(),
        ]);

        $available = $this->evidence($organization, $actor, 'S086-AVAILABLE', '1000.00');
        $partial = $this->evidence($organization, $actor, 'S086-PARTIAL', '1000.00');
        $full = $this->evidence($organization, $actor, 'S086-FULL', '500.00');
        $foreign = $this->evidence($foreignOrganization, $actor, 'S086-FOREIGN', '700.00');
        $partialLink = $this->settlementLink($organization, $actor, $document, $statement, $partial, 40000, 'partial');
        $fullLink = $this->settlementLink($organization, $actor, $document, $statement, $full, 50000, 'full');
        $reconciliation = FinancialSettlementBankPaymentReconciliation::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_payment_id' => $fullLink['payment']->id,
            'financial_settlement_statement_id' => $statement->id,
            'bank_transaction_evidence_id' => $full->id, 'payment_revision' => 1,
            'status' => FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED,
            'revision' => 1, 'reason' => 'Confirmed S086 evidence link.',
            'confirmed_by_user_id' => $actor->id, 'confirmed_at' => now(),
        ]);
        BankTransactionEvidenceEvent::query()->create([
            'public_id' => (string) Str::uuid(), 'bank_transaction_evidence_id' => $partial->id,
            'event_type' => 'bank_transaction_evidence_recorded', 'evidence' => ['source_reference' => 'S086-PARTIAL'],
            'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now(),
        ]);
        BankTransactionAmountBreakdown::query()->create([
            'public_id' => (string) Str::uuid(), 'breakdown_uid' => (string) Str::uuid(),
            'organization_context_id' => $organization->id, 'bank_transaction_evidence_id' => $partial->id,
            'idempotency_key' => (string) Str::uuid(), 'revision' => 1, 'status' => 'finalized',
            'source_amount_minor' => 100000, 'allocated_amount_minor' => 100000, 'currency' => 'CZK',
            'reason' => 'S086 runtime breakdown.', 'created_by_user_id' => $actor->id, 'recorded_at' => now(),
        ]);

        $snapshots = [
            'available' => $available->only(['status', 'revision', 'amount']),
            'partial' => $partial->only(['status', 'revision', 'amount']),
            'full' => $full->only(['status', 'revision', 'amount']),
            'foreign' => $foreign->only(['status', 'revision', 'amount']),
            'statement' => $statement->only(['status', 'revision', 'net_balance_minor']),
            'document' => $document->only(['status', 'gross_amount']),
            'partial_payment' => $partialLink['payment']->only(['status', 'revision', 'allocated_amount_minor']),
            'full_payment' => $fullLink['payment']->only(['status', 'revision', 'allocated_amount_minor']),
            'reconciliation' => $reconciliation->only(['status', 'revision', 'reason']),
        ];

        $index = $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/bank-transaction-evidence?direction=debit&currency=CZK&booked_from=2026-09-01&booked_until=2026-09-30&reference=S086');
        $index->assertOk()->assertJsonPath('data.pagination.total', 3)->assertJsonPath('data.read_only', true);
        $states = collect($index->json('data.items'))->pluck('capacity_state', 'source_reference')->all();
        self::assertSame('available', $states['S086-AVAILABLE']);
        self::assertSame('partially_allocated', $states['S086-PARTIAL']);
        self::assertSame('fully_allocated', $states['S086-FULL']);
        self::assertArrayNotHasKey('S086-FOREIGN', $states);

        $filtered = $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/bank-transaction-evidence?capacity=partially_allocated');
        $filtered->assertOk()->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.public_id', (string) $partial->public_id)
            ->assertJsonPath('data.items.0.allocated_amount_minor', 40000)
            ->assertJsonPath('data.items.0.remaining_capacity_amount_minor', 60000);

        $partialDetail = $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/bank-transaction-evidence/'.$partial->public_id);
        $partialDetail->assertOk()->assertJsonPath('data.read_only', true)
            ->assertJsonCount(1, 'data.settlement_candidates')
            ->assertJsonCount(1, 'data.settlement_payments')
            ->assertJsonCount(0, 'data.reconciliations')
            ->assertJsonCount(1, 'data.amount_breakdowns')
            ->assertJsonCount(1, 'data.events')
            ->assertJsonPath('data.payment_mutated', false)
            ->assertJsonPath('data.reconciliation_mutated', false)
            ->assertJsonPath('data.accounting_posting_performed', false);

        $fullDetail = $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/bank-transaction-evidence/'.$full->public_id);
        $fullDetail->assertOk()->assertJsonCount(1, 'data.reconciliations')
            ->assertJsonPath('data.reconciliations.0.status', FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED);
        $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/bank-transaction-evidence/'.$foreign->public_id)->assertNotFound();

        self::assertSame($snapshots['available'], $available->fresh()->only(array_keys($snapshots['available'])));
        self::assertSame($snapshots['partial'], $partial->fresh()->only(array_keys($snapshots['partial'])));
        self::assertSame($snapshots['full'], $full->fresh()->only(array_keys($snapshots['full'])));
        self::assertSame($snapshots['foreign'], $foreign->fresh()->only(array_keys($snapshots['foreign'])));
        self::assertSame($snapshots['statement'], $statement->fresh()->only(array_keys($snapshots['statement'])));
        self::assertSame($snapshots['document'], $document->fresh()->only(array_keys($snapshots['document'])));
        self::assertSame($snapshots['partial_payment'], $partialLink['payment']->fresh()->only(array_keys($snapshots['partial_payment'])));
        self::assertSame($snapshots['full_payment'], $fullLink['payment']->fresh()->only(array_keys($snapshots['full_payment'])));
        self::assertSame($snapshots['reconciliation'], $reconciliation->fresh()->only(array_keys($snapshots['reconciliation'])));
    }

    private function evidence(Organization $organization, User $actor, string $reference, string $amount): BankTransactionEvidence
    {
        return BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => $reference, 'direction' => 'debit', 'booked_at' => '2026-09-18',
            'value_date' => '2026-09-18', 'amount' => $amount, 'currency' => 'CZK',
            'counterparty_name' => 'S086 carrier', 'evidence_note' => 'S086 runtime evidence.',
            'status' => 'recorded', 'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
    }

    /** @return array{candidate: FinancialSettlementBankMatchCandidate, payment: FinancialSettlementBankPayment} */
    private function settlementLink(Organization $organization, User $actor, BillingDocument $document, FinancialSettlementStatement $statement, BankTransactionEvidence $evidence, int $amountMinor, string $suffix): array
    {
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'candidate_fingerprint' => hash('sha256', 's086-'.$suffix.'-candidate'),
            'currency' => 'CZK', 'expected_bank_direction' => 'debit',
            'bank_amount_minor' => (int) round((float) $evidence->getAttribute('amount') * 100),
            'settlement_outstanding_amount_minor' => 100000, 'proposed_amount_minor' => $amountMinor,
            'score_basis_points' => 9000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
            'match_reasons' => ['runtime' => true], 'source_snapshot' => [], 'revision' => 2,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(),
            'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now(), 'review_reason' => 'Accepted S086 runtime candidate.',
        ]);
        $payment = FinancialSettlementBankPayment::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_match_candidate_id' => $candidate->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's086-'.$suffix.'-payment'),
            'allocated_amount_minor' => $amountMinor, 'currency' => 'CZK', 'status' => FinancialSettlementBankPayment::STATUS_ACTIVE,
            'reason' => 'Existing S086 settlement payment.', 'allocated_by_user_id' => $actor->id,
            'allocated_at' => now(), 'revision' => 1,
        ]);

        return ['candidate' => $candidate, 'payment' => $payment];
    }
}
