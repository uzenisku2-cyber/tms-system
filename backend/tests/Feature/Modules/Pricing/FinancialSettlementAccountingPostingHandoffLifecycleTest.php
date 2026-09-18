<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoffEvent;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Services\FinancialSettlementAccountingPostingHandoffService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingHandoffLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_handoff_preparation_is_scoped_idempotent_append_only_and_non_mutating(): void
    {
        $organization = Organization::query()->create(['name' => 'S087 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'S087 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S087 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'counterparty_organization_id' => $carrier->id, 'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => '500.00', 'vat_amount' => '0.00', 'gross_amount' => '500.00',
            'status' => 'draft', 'source_snapshot' => [], 'created_by_user_id' => $actor->id,
        ]);
        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->id, 'period_from' => '2026-09-01', 'period_until' => '2026-09-30',
            'currency' => 'CZK', 'status' => FinancialSettlementStatement::STATUS_CLOSED,
            'earning_amount_minor' => 50000, 'deduction_amount_minor' => 0, 'net_balance_minor' => 50000,
            'source_snapshot' => [], 'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', 's087-statement'), 'revision' => 5,
            'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => 'payable', 'output_materialized_at' => now(),
        ]);
        $evidence = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S087-001', 'direction' => 'debit', 'booked_at' => '2026-09-19',
            'value_date' => '2026-09-19', 'amount' => '500.00', 'currency' => 'CZK',
            'evidence_note' => 'S087 runtime evidence.', 'status' => 'recorded',
            'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'candidate_fingerprint' => hash('sha256', 's087-candidate'),
            'currency' => 'CZK', 'expected_bank_direction' => 'debit', 'bank_amount_minor' => 50000,
            'settlement_outstanding_amount_minor' => 50000, 'proposed_amount_minor' => 50000,
            'score_basis_points' => 10000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
            'match_reasons' => ['amount_exact' => true], 'source_snapshot' => [], 'revision' => 2,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(), 'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(), 'review_reason' => 'Accepted S087 candidate.',
        ]);
        $payment = FinancialSettlementBankPayment::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_match_candidate_id' => $candidate->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's087-payment'),
            'allocated_amount_minor' => 50000, 'currency' => 'CZK', 'status' => FinancialSettlementBankPayment::STATUS_ACTIVE,
            'reason' => 'Existing settlement payment.', 'allocated_by_user_id' => $actor->id,
            'allocated_at' => now(), 'revision' => 1,
        ]);
        $reconciliation = FinancialSettlementBankPaymentReconciliation::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_payment_id' => $payment->id,
            'financial_settlement_statement_id' => $statement->id,
            'bank_transaction_evidence_id' => $evidence->id, 'payment_revision' => 1,
            'status' => FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED,
            'revision' => 1, 'reason' => 'Confirmed S087 reconciliation.',
            'confirmed_by_user_id' => $actor->id, 'confirmed_at' => now(),
        ]);
        $snapshots = [
            'statement' => $statement->only(['status', 'revision', 'net_balance_minor']),
            'payment' => $payment->only(['status', 'revision', 'allocated_amount_minor']),
            'reconciliation' => $reconciliation->only(['status', 'revision', 'reason']),
            'document' => $document->only(['status', 'gross_amount']),
            'evidence' => $evidence->only(['status', 'revision', 'amount']),
        ];
        $command = [
            'idempotency_key' => (string) Str::uuid(),
            'expected_payment_revision' => 1,
            'expected_reconciliation_revision' => 1,
            'posting_date' => '2026-09-19',
            'accounting_reference' => 'ACC-S087-001',
            'reason' => 'Prepare accounting posting handoff.',
        ];
        $service = app(FinancialSettlementAccountingPostingHandoffService::class);

        $created = $service->prepare((string) $statement->public_id, (string) $payment->public_id, $command, (int) $organization->id, $actor);
        self::assertFalse($created['replayed']);
        self::assertSame(FinancialSettlementAccountingPostingHandoff::STATUS_PREPARED, $created['data']['status']);
        self::assertSame(50000, $created['data']['amount_minor']);
        self::assertFalse($created['data']['accounting_posting_performed']);
        self::assertFalse($created['data']['ledger_entry_created']);
        self::assertCount(1, $created['data']['events']);

        $replayed = $service->prepare((string) $statement->public_id, (string) $payment->public_id, $command, (int) $organization->id, $actor);
        self::assertTrue($replayed['replayed']);
        self::assertSame($created['data']['public_id'], $replayed['data']['public_id']);
        self::assertSame(1, FinancialSettlementAccountingPostingHandoff::query()->count());
        self::assertSame(1, FinancialSettlementAccountingPostingHandoffEvent::query()->count());

        try {
            $service->prepare((string) $statement->public_id, (string) $payment->public_id, array_merge($command, ['reason' => 'Conflicting replay.']), (int) $organization->id, $actor);
            self::fail('A conflicting idempotency replay must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('idempotency_key', $exception->errors());
        }

        try {
            $service->prepare((string) $statement->public_id, (string) $payment->public_id, array_merge($command, ['idempotency_key' => (string) Str::uuid(), 'expected_payment_revision' => 2]), (int) $organization->id, $actor);
            self::fail('A stale payment revision must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_payment_revision', $exception->errors());
        }

        try {
            $service->prepare((string) $statement->public_id, (string) $payment->public_id, array_merge($command, ['idempotency_key' => (string) Str::uuid()]), (int) $foreignOrganization->id, $actor);
            self::fail('A foreign organization must not resolve the payment.');
        } catch (ModelNotFoundException) {
            // Expected boundary rejection.
        }

        self::assertSame($snapshots['statement'], $statement->fresh()->only(array_keys($snapshots['statement'])));
        self::assertSame($snapshots['payment'], $payment->fresh()->only(array_keys($snapshots['payment'])));
        self::assertSame($snapshots['reconciliation'], $reconciliation->fresh()->only(array_keys($snapshots['reconciliation'])));
        self::assertSame($snapshots['document'], $document->fresh()->only(array_keys($snapshots['document'])));
        self::assertSame($snapshots['evidence'], $evidence->fresh()->only(array_keys($snapshots['evidence'])));

        $handoff = FinancialSettlementAccountingPostingHandoff::query()->firstOrFail();
        $event = FinancialSettlementAccountingPostingHandoffEvent::query()->firstOrFail();
        try {
            $handoff->update(['reason' => 'Forbidden mutation.']);
            self::fail('The handoff must be append-only.');
        } catch (\RuntimeException) {
            // Expected boundary rejection.
        }
        try {
            $event->update(['event_type' => 'forbidden_mutation']);
            self::fail('The handoff event must be append-only.');
        } catch (\RuntimeException) {
            // Expected boundary rejection.
        }
    }
}
