<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementBankMatchCandidateFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_settlement_bank_match_candidate_tables_are_installed_without_payment_execution(): void
    {
        self::assertTrue(Schema::hasColumns('financial_settlement_bank_match_candidates', [
            'public_id', 'owner_organization_id', 'financial_settlement_statement_id',
            'billing_document_id', 'bank_transaction_evidence_id',
            'bank_transaction_evidence_revision', 'idempotency_key', 'candidate_fingerprint',
            'currency', 'expected_bank_direction', 'bank_amount_minor',
            'settlement_outstanding_amount_minor', 'proposed_amount_minor',
            'score_basis_points', 'status', 'match_reasons', 'source_snapshot',
            'revision', 'proposed_by_user_id', 'proposed_at', 'reviewed_by_user_id',
            'reviewed_at', 'review_reason',
        ]));
        self::assertTrue(Schema::hasColumns('financial_settlement_bank_match_candidate_events', [
            'public_id', 'financial_settlement_bank_match_candidate_id', 'revision',
            'event_type', 'idempotency_key', 'candidate_fingerprint', 'reason',
            'evidence', 'actor_user_id', 'occurred_at',
        ]));

        foreach (['payment_id', 'payment_allocation_id', 'bank_matching_execution_id', 'materialized_at'] as $forbidden) {
            self::assertFalse(Schema::hasColumn('financial_settlement_bank_match_candidates', $forbidden));
        }
    }
}
