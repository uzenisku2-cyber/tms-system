<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankMatchCandidateContractTest extends TestCase
{
    public function test_candidate_foundation_is_directional_reviewable_and_non_executing(): void
    {
        $model = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialSettlementBankMatchCandidate.php');
        $event = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/FinancialSettlementBankMatchCandidateEvent.php');
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_16_120000_create_financial_settlement_bank_match_candidate_foundation.php');
        self::assertIsString($model);
        self::assertIsString($event);
        self::assertIsString($migration);

        foreach ([
            'financial_settlement_statement_id', 'billing_document_id',
            'bank_transaction_evidence_id', 'bank_transaction_evidence_revision',
            'expected_bank_direction', 'candidate_fingerprint',
            'settlement_outstanding_amount_minor', 'proposed_amount_minor',
            'score_basis_points', 'match_reasons', 'source_snapshot',
            'idempotency_key', 'revision',
        ] as $marker) {
            self::assertStringContainsString($marker, $model.$migration);
        }
        foreach ([
            "STATUS_PROPOSED = 'proposed'", "STATUS_ACCEPTED = 'accepted'",
            "STATUS_REJECTED = 'rejected'", "STATUS_SUPERSEDED = 'superseded'",
        ] as $marker) {
            self::assertStringContainsString($marker, $model);
        }
        self::assertStringContainsString("expected_bank_direction IN ('debit','credit')", $migration);
        self::assertStringContainsString('score_basis_points BETWEEN 1 AND 10000', $migration);
        self::assertStringContainsString('append-only', $event);

        foreach ([
            'SupplierFuelInvoiceBankPayment', 'VehicleCostAllocationBankMatchingExecution',
            'payment_id', 'payment_allocation_id', 'bank_matching_execution_id',
            'materialized_by_user_id', 'materialized_at', "'payment_marked' => true",
            "'bank_matching_performed' => true",
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $model.$event.$migration);
        }
    }
}
