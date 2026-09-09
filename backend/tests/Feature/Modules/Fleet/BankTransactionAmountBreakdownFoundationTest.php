<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BankTransactionAmountBreakdownFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_minor_unit_breakdown_tables_are_installed_without_payment_execution_columns(): void
    {
        self::assertTrue(Schema::hasColumns('bank_transaction_amount_breakdowns', ['public_id', 'breakdown_uid', 'organization_context_id', 'bank_transaction_evidence_id', 'idempotency_key', 'revision', 'status', 'source_amount_minor', 'allocated_amount_minor', 'currency', 'reason', 'created_by_user_id', 'recorded_at']));
        self::assertTrue(Schema::hasColumns('bank_transaction_amount_breakdown_components', ['bank_transaction_amount_breakdown_id', 'sequence_number', 'component_type', 'label', 'amount_minor', 'metadata']));
        self::assertTrue(Schema::hasColumns('bank_transaction_amount_breakdown_events', ['bank_transaction_amount_breakdown_id', 'event_type', 'evidence', 'actor_user_id', 'occurred_at']));

        foreach (['payment_id', 'billing_document_id', 'matching_execution_id', 'paid_at'] as $column) {
            self::assertFalse(Schema::hasColumn('bank_transaction_amount_breakdowns', $column));
        }
    }
}
