<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BankTransactionEvidenceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transaction_evidence_tables_are_installed_without_matching_or_payment_execution(): void
    {
        self::assertTrue(Schema::hasTable('bank_transaction_evidence'));
        self::assertTrue(Schema::hasTable('bank_transaction_evidence_events'));
        foreach (['public_id', 'organization_context_id', 'idempotency_key', 'source_type', 'source_reference', 'direction', 'booked_at', 'amount', 'currency', 'status', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('bank_transaction_evidence', $column), $column);
        }
        self::assertFalse(Schema::hasColumn('bank_transaction_evidence', 'billing_document_id'));
        self::assertFalse(Schema::hasColumn('bank_transaction_evidence', 'payment_id'));
        self::assertFalse(Schema::hasColumn('bank_transaction_evidence', 'bank_matching_handoff_id'));
    }
}
