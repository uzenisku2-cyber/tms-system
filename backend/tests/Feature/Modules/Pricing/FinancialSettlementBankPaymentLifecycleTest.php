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
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementBankPaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_accepted_candidate_materializes_exactly_once_without_mutating_sources(): void
    {
        $organization = Organization::query()->create(['name' => 'S080 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $carrier = Organization::query()->create(['name' => 'S080 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
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
            'counterparty_organization_id' => $carrier->id, 'driver_id' => null,
            'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => '1250.00', 'vat_rate' => null, 'vat_amount' => '0.00', 'gross_amount' => '1250.00',
            'status' => 'draft', 'source_snapshot' => [], 'created_by_user_id' => $actor->id,
        ]);
        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->id, 'recipient_driver_id' => null,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'status' => FinancialSettlementStatement::STATUS_CLOSED, 'earning_amount_minor' => 125000,
            'deduction_amount_minor' => 0, 'net_balance_minor' => 125000, 'source_snapshot' => [],
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's080-statement'),
            'revision' => 5, 'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => 'payable', 'output_materialized_at' => now(),
        ]);
        $evidence = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S080-001', 'bank_statement_reference' => 'S080',
            'direction' => 'debit', 'booked_at' => '2026-09-17', 'value_date' => '2026-09-17',
            'amount' => '1250.00', 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'S080 carrier', 'counterparty_account_identifier' => '123/0100',
            'variable_symbol' => '80001', 'message' => 'Settlement payment', 'evidence_note' => 'Runtime probe.',
            'status' => 'recorded', 'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $candidate = FinancialSettlementBankMatchCandidate::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'financial_settlement_statement_id' => $statement->id, 'billing_document_id' => $document->id,
            'bank_transaction_evidence_id' => $evidence->id, 'bank_transaction_evidence_revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'candidate_fingerprint' => hash('sha256', 's080-candidate'),
            'currency' => 'CZK', 'expected_bank_direction' => 'debit', 'bank_amount_minor' => 125000,
            'settlement_outstanding_amount_minor' => 125000, 'proposed_amount_minor' => 125000,
            'score_basis_points' => 10000, 'status' => FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
            'match_reasons' => ['amount_exact' => true], 'source_snapshot' => [], 'revision' => 2,
            'proposed_by_user_id' => $actor->id, 'proposed_at' => now(), 'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(), 'review_reason' => 'Accepted for exact runtime materialization.',
        ]);

        $input = ['idempotency_key' => (string) Str::uuid(), 'expected_candidate_revision' => 2, 'reason' => 'Approved exact settlement payment.'];
        $service = app(FinancialSettlementBankPaymentService::class);
        $first = $service->materialize((string) $statement->public_id, (string) $candidate->public_id, $input, (int) $organization->id, $actor);
        $again = $service->materialize((string) $statement->public_id, (string) $candidate->public_id, $input, (int) $organization->id, $actor);

        self::assertFalse($first['replayed']);
        self::assertTrue($again['replayed']);
        self::assertSame($first['data']['public_id'], $again['data']['public_id']);
        self::assertSame(125000, $first['data']['allocated_amount_minor']);
        self::assertSame('paid', $first['data']['settlement_payment_state']);
        self::assertSame(125000, $first['data']['bank_transaction_allocated_amount_minor']);
        self::assertSame(0, $first['data']['bank_transaction_unallocated_amount_minor']);
        self::assertTrue($first['data']['bank_matching_performed']);
        self::assertTrue($first['data']['payment_marked']);
        self::assertFalse($first['data']['billing_document_modified']);
        self::assertFalse($first['data']['settlement_statement_modified']);
        self::assertSame(5, (int) $statement->fresh()->revision);
        self::assertSame('draft', $document->fresh()->status);
        self::assertDatabaseCount('financial_settlement_bank_payments', 1);
        self::assertDatabaseCount('financial_settlement_bank_payment_events', 1);
        self::assertSame(FinancialSettlementBankPayment::STATUS_ACTIVE, FinancialSettlementBankPayment::query()->firstOrFail()->status);

        $stale = ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'Stale reversal must fail.'];
        $stale['expected_revision'] = 99;
        try {
            $service->reverse((string) $statement->public_id, (string) $first['data']['public_id'], $stale, (int) $organization->id, $actor);
            self::fail('Stale reversal unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }

        $reverseInput = ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Reverse the exact settlement payment.'];
        $reversed = $service->reverse((string) $statement->public_id, (string) $first['data']['public_id'], $reverseInput, (int) $organization->id, $actor);
        $reverseReplay = $service->reverse((string) $statement->public_id, (string) $first['data']['public_id'], $reverseInput, (int) $organization->id, $actor);
        self::assertFalse($reversed['replayed']);
        self::assertTrue($reverseReplay['replayed']);
        self::assertSame(FinancialSettlementBankPayment::STATUS_REVERSED, $reversed['data']['status']);
        self::assertSame('unpaid', $reversed['data']['settlement_payment_state']);
        self::assertSame(0, $reversed['data']['bank_transaction_allocated_amount_minor']);
        self::assertSame(125000, $reversed['data']['bank_transaction_unallocated_amount_minor']);
        self::assertFalse($reversed['data']['bank_matching_performed']);
        self::assertFalse($reversed['data']['payment_marked']);
        self::assertDatabaseCount('financial_settlement_bank_payment_events', 2);

        try {
            $service->reverse((string) $statement->public_id, (string) $first['data']['public_id'], ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'A second reversal must fail.'], (int) $organization->id, $actor);
            self::fail('Second reversal unexpectedly succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('payment', $exception->errors());
        }

        $secondCandidate = $candidate->replicate(['public_id', 'idempotency_key', 'candidate_fingerprint']);
        $secondCandidate->public_id = (string) Str::uuid();
        $secondCandidate->idempotency_key = (string) Str::uuid();
        $secondCandidate->candidate_fingerprint = hash('sha256', 's080-second-candidate');
        $secondCandidate->save();
        $second = $service->materialize((string) $statement->public_id, (string) $secondCandidate->public_id, ['idempotency_key' => (string) Str::uuid(), 'expected_candidate_revision' => 2, 'reason' => 'Reuse released shared bank capacity.'], (int) $organization->id, $actor);
        self::assertFalse($second['replayed']);
        self::assertSame(125000, $second['data']['bank_transaction_allocated_amount_minor']);
        self::assertSame(0, $second['data']['bank_transaction_unallocated_amount_minor']);
        self::assertDatabaseCount('financial_settlement_bank_payments', 2);
        self::assertDatabaseCount('financial_settlement_bank_payment_events', 3);
        self::assertSame(5, (int) $statement->fresh()->revision);
        self::assertSame('draft', $document->fresh()->status);
    }
}
