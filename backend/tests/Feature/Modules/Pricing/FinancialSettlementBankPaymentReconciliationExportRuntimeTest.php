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
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentReconciliationCsvExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementBankPaymentReconciliationExportRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_filtered_csv_is_scoped_utf8_business_readable_and_non_mutating(): void
    {
        $organization = Organization::query()->create(['name' => 'S085 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'S085 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S085 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
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
            'command_fingerprint' => hash('sha256', 's085-statement'), 'revision' => 5,
            'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => 'payable', 'output_materialized_at' => now(),
        ]);
        $evidence = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S085-001', 'direction' => 'debit', 'booked_at' => '2026-09-18',
            'value_date' => '2026-09-18', 'amount' => '500.00', 'currency' => 'CZK',
            'evidence_note' => 'S085 runtime evidence.', 'status' => 'recorded',
            'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'candidate_fingerprint' => hash('sha256', 's085-candidate'),
            'currency' => 'CZK', 'expected_bank_direction' => 'debit', 'bank_amount_minor' => 50000,
            'settlement_outstanding_amount_minor' => 50000, 'proposed_amount_minor' => 50000,
            'score_basis_points' => 10000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
            'match_reasons' => ['amount_exact' => true], 'source_snapshot' => [], 'revision' => 2,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(), 'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(), 'review_reason' => 'Accepted S085 candidate.',
        ]);
        $payment = FinancialSettlementBankPayment::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_bank_match_candidate_id' => $candidate->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's085-payment'),
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
            'revision' => 1, 'reason' => 'Confirmed S085 reconciliation.',
            'confirmed_by_user_id' => $actor->id, 'confirmed_at' => '2026-09-19 08:30:00',
        ]);

        $snapshots = [
            'statement' => $statement->only(['status', 'revision', 'net_balance_minor']),
            'payment' => $payment->only(['status', 'revision', 'allocated_amount_minor']),
            'reconciliation' => $reconciliation->only(['status', 'revision', 'reason']),
            'document' => $document->only(['status', 'gross_amount']),
            'evidence' => $evidence->only(['status', 'revision', 'amount']),
        ];

        $response = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->get('/api/v1/financial-settlement-statements/reconciliation-export?status=closed&party_type=organization&direction=payable&period_from=2026-09-01&period_until=2026-09-30');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
        self::assertStringContainsString("Vy\u{00FA}\u{010D}tov\u{00E1}n\u{00ED};P\u{0159}\u{00ED}jemce", $csv);
        self::assertStringContainsString('BANK-S085-001', $csv);
        self::assertStringContainsString('500,00', $csv);
        self::assertStringContainsString('Potvrzeno', $csv);

        $excluded = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->get('/api/v1/financial-settlement-statements/reconciliation-export?status=draft');
        $excluded->assertOk();
        self::assertStringNotContainsString('BANK-S085-001', $excluded->streamedContent());

        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        $foreignRows = app(FinancialSettlementBankPaymentReconciliationCsvExportService::class)
            ->write((int) $foreignOrganization->id, $actor, [], $stream);
        fclose($stream);
        self::assertSame(0, $foreignRows);

        self::assertSame($snapshots['statement'], $statement->fresh()->only(array_keys($snapshots['statement'])));
        self::assertSame($snapshots['payment'], $payment->fresh()->only(array_keys($snapshots['payment'])));
        self::assertSame($snapshots['reconciliation'], $reconciliation->fresh()->only(array_keys($snapshots['reconciliation'])));
        self::assertSame($snapshots['document'], $document->fresh()->only(array_keys($snapshots['document'])));
        self::assertSame($snapshots['evidence'], $evidence->fresh()->only(array_keys($snapshots['evidence'])));
    }
}
