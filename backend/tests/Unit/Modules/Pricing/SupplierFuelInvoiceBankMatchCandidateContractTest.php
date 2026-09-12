<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class SupplierFuelInvoiceBankMatchCandidateContractTest extends TestCase
{
    public function test_candidate_foundation_is_explainable_reviewable_and_non_executing(): void
    {
        $model = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/SupplierFuelInvoiceBankMatchCandidate.php');
        $event = file_get_contents(__DIR__.'/../../../../app/Modules/Pricing/Models/SupplierFuelInvoiceBankMatchCandidateEvent.php');
        $migration = file_get_contents(__DIR__.'/../../../../database/migrations/2026_09_16_000000_create_supplier_fuel_invoice_bank_match_candidate_foundation.php');
        self::assertIsString($model);
        self::assertIsString($event);
        self::assertIsString($migration);

        foreach (['billing_document_id', 'bank_transaction_evidence_id', 'bank_transaction_evidence_revision', 'candidate_fingerprint', 'proposed_amount_minor', 'score_basis_points', 'match_reasons', 'source_snapshot', 'idempotency_key', 'revision'] as $marker) {
            self::assertStringContainsString($marker, $model.$migration);
        }
        foreach (["STATUS_PROPOSED = 'proposed'", "STATUS_ACCEPTED = 'accepted'", "STATUS_REJECTED = 'rejected'", "STATUS_SUPERSEDED = 'superseded'"] as $marker) {
            self::assertStringContainsString($marker, $model);
        }
        self::assertStringContainsString('append-only', $event);
        self::assertStringContainsString('score_basis_points <= 10000', $migration);
        foreach (['SupplierFuelInvoiceBankPayment::query()->create', 'VehicleCostAllocationBankMatchingExecution::query()->create', "'payment_marked' => true", 'FinancialSettlementStatement::query()'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $model.$event.$migration);
        }
    }
}
