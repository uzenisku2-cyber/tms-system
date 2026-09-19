<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingExecutionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_posting_execution_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_executions'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_entries'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_execution_events'));
        foreach (['owner_organization_id', 'financial_settlement_accounting_posting_handoff_id', 'idempotency_key', 'command_fingerprint', 'handoff_revision', 'posting_date', 'amount_minor', 'currency', 'direction', 'status', 'source_snapshot', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_accounting_posting_executions', $column));
        }
    }
}
