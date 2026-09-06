<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class BankTransactionEvidenceContractTest extends TestCase
{
    public function test_foundation_is_explicit_idempotent_append_only_and_non_matching(): void
    {
        $root = __DIR__.'/../../../../';
        $service = file_get_contents($root.'app/Modules/Fleet/Services/BankTransactionEvidenceService.php');
        $migration = file_get_contents($root.'database/migrations/2026_09_09_120000_create_bank_transaction_evidence_foundation.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        foreach (['compensation.manage', 'idempotency_key', 'source_reference', 'bank_matching_performed', 'payment_marked', 'invoice_modified', 'deposit_offset_performed', 'repair_fund_movement_performed'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (['bte_org_idempotency_unique', 'bte_org_source_reference_unique', 'btee_evidence_revision_unique', 'credit', 'debit'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }
        foreach (['BillingDocument::query()->', 'VehicleCostAllocationBankMatchingHandoff::query()->', 'payment_id', 'billing_document_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
