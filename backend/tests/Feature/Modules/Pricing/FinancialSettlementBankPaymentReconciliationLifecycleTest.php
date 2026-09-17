<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliationEvent;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentReconciliationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementBankPaymentReconciliationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_confirm_reopen_and_reconfirm_are_idempotent_scoped_audited_and_non_mutating(): void
    {
        $organization = Organization::query()->create(['name' => 'S083 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'S083 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
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
            'command_fingerprint' => hash('sha256', 's083-statement'), 'revision' => 5,
            'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => 'payable', 'output_materialized_at' => now(),
        ]);
        $evidence = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S083-001', 'direction' => 'debit', 'booked_at' => '2026-09-18',
            'value_date' => '2026-09-18', 'amount' => '500.00', 'currency' => 'CZK',
            'evidence_note' => 'S083 runtime evidence.', 'status' => 'recorded',
            'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'candidate_fingerprint' => hash('sha256', 's083-candidate'),
            'currency' => 'CZK', 'expected_bank_direction' => 'debit', 'bank_amount_minor' => 50000,
            'settlement_outstanding_amount_minor' => 50000, 'proposed_amount_minor' => 50000,
            'score_basis_points' => 10000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
            'match_reasons' => ['amount_exact' => true], 'source_snapshot' => [], 'revision' => 2,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(), 'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(), 'review_reason' => 'Accepted S083 candidate.',
        ]);
        $payment = FinancialSettlementBankPayment::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_match_candidate_id' => $candidate->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's083-payment'),
            'allocated_amount_minor' => 50000, 'currency' => 'CZK', 'status' => FinancialSettlementBankPayment::STATUS_ACTIVE,
            'reason' => 'Existing settlement payment.', 'allocated_by_user_id' => $actor->id,
            'allocated_at' => now(), 'revision' => 1,
        ]);

        $paymentSnapshot = $payment->only(['status', 'revision', 'allocated_amount_minor']);
        $statementSnapshot = $statement->only(['status', 'revision', 'net_balance_minor']);
        $documentSnapshot = $document->only(['status', 'gross_amount']);
        $evidenceSnapshot = $evidence->only(['status', 'revision', 'amount']);
        $service = app(FinancialSettlementBankPaymentReconciliationService::class);

        $confirmInput = ['idempotency_key' => (string) Str::uuid(), 'expected_payment_revision' => 1, 'expected_reconciliation_revision' => 0, 'reason' => 'Confirm reviewed settlement payment.'];
        $confirmed = $service->confirm((string) $statement->public_id, (string) $payment->public_id, $confirmInput, (int) $organization->id, $actor);
        $confirmReplay = $service->confirm((string) $statement->public_id, (string) $payment->public_id, $confirmInput, (int) $organization->id, $actor);
        self::assertFalse($confirmed['replayed']);
        self::assertTrue($confirmed['created']);
        self::assertTrue($confirmReplay['replayed']);
        self::assertSame(FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED, $confirmed['data']['status']);
        self::assertSame(1, $confirmed['data']['revision']);

        try {
            $changedReplay = $confirmInput;
            $changedReplay['reason'] = 'A different command body.';
            $service->confirm((string) $statement->public_id, (string) $payment->public_id, $changedReplay, (int) $organization->id, $actor);
            self::fail('Changed idempotent replay unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('idempotency_key', $exception->errors());
        }

        try {
            $service->confirm((string) $statement->public_id, (string) $payment->public_id, ['idempotency_key' => (string) Str::uuid(), 'expected_payment_revision' => 1, 'expected_reconciliation_revision' => 0, 'reason' => 'Reject stale reconciliation revision.'], (int) $organization->id, $actor);
            self::fail('Stale reconciliation confirmation unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_reconciliation_revision', $exception->errors());
        }

        $reopenInput = ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Reopen reconciliation for another review.'];
        $reopened = $service->reopen((string) $statement->public_id, (string) $payment->public_id, $reopenInput, (int) $organization->id, $actor);
        $reopenReplay = $service->reopen((string) $statement->public_id, (string) $payment->public_id, $reopenInput, (int) $organization->id, $actor);
        self::assertFalse($reopened['replayed']);
        self::assertTrue($reopenReplay['replayed']);
        self::assertSame(FinancialSettlementBankPaymentReconciliation::STATUS_OPEN, $reopened['data']['status']);
        self::assertSame(2, $reopened['data']['revision']);

        try {
            $service->reopen((string) $statement->public_id, (string) $payment->public_id, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'Second reopen must fail.'], (int) $organization->id, $actor);
            self::fail('Second reopen unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('reconciliation', $exception->errors());
        }

        $reconfirmed = $service->confirm((string) $statement->public_id, (string) $payment->public_id, ['idempotency_key' => (string) Str::uuid(), 'expected_payment_revision' => 1, 'expected_reconciliation_revision' => 2, 'reason' => 'Confirm the reviewed payment again.'], (int) $organization->id, $actor);
        self::assertFalse($reconfirmed['created']);
        self::assertSame(FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED, $reconfirmed['data']['status']);
        self::assertSame(3, $reconfirmed['data']['revision']);
        self::assertDatabaseCount('financial_settlement_bank_payment_reconciliations', 1);
        self::assertDatabaseCount('financial_settlement_bank_payment_reconciliation_events', 3);
        self::assertSame(['reconciliation_confirmed', 'reconciliation_reopened', 'reconciliation_confirmed'], FinancialSettlementBankPaymentReconciliationEvent::query()->orderBy('revision')->pluck('event_type')->all());

        $foreignOrganization = Organization::query()->create(['name' => 'S083 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        try {
            $service->reopen((string) $statement->public_id, (string) $payment->public_id, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'Foreign scope must fail.'], (int) $foreignOrganization->id, $actor);
            self::fail('Foreign organization unexpectedly accessed reconciliation.');
        } catch (ModelNotFoundException $exception) {
            self::assertSame(FinancialSettlementBankPayment::class, $exception->getModel());
        }

        self::assertSame($paymentSnapshot, $payment->fresh()->only(array_keys($paymentSnapshot)));
        self::assertSame($statementSnapshot, $statement->fresh()->only(array_keys($statementSnapshot)));
        self::assertSame($documentSnapshot, $document->fresh()->only(array_keys($documentSnapshot)));
        self::assertSame($evidenceSnapshot, $evidence->fresh()->only(array_keys($evidenceSnapshot)));
    }
}
