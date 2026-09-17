<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementBankPaymentReconciliationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_projection_and_append_only_event_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_bank_payment_reconciliations'));
        self::assertTrue(Schema::hasTable('financial_settlement_bank_payment_reconciliation_events'));
        foreach (['owner_organization_id', 'financial_settlement_bank_payment_id', 'financial_settlement_statement_id', 'bank_transaction_evidence_id', 'payment_revision', 'status', 'revision', 'confirmed_by_user_id', 'confirmed_at', 'reopened_by_user_id', 'reopened_at'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_bank_payment_reconciliations', $column), $column);
        }
        foreach (['owner_organization_id', 'financial_settlement_bank_payment_reconciliation_id', 'revision', 'event_type', 'from_status', 'to_status', 'idempotency_key', 'command_fingerprint', 'evidence', 'actor_user_id', 'occurred_at'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_bank_payment_reconciliation_events', $column), $column);
        }
    }
}
