<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingHandoffFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_posting_handoff_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_handoffs'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_handoff_events'));
        foreach ([
            'owner_organization_id', 'financial_settlement_bank_payment_reconciliation_id',
            'financial_settlement_bank_payment_id', 'financial_settlement_statement_id',
            'bank_transaction_evidence_id', 'idempotency_key', 'command_fingerprint',
            'reconciliation_revision', 'payment_revision', 'posting_date', 'amount_minor',
            'currency', 'direction', 'status', 'source_snapshot', 'revision',
        ] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_accounting_posting_handoffs', $column));
        }
    }
}
