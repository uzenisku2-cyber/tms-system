<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingReversalFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reversal_foundation_is_installed_without_mutating_financial_sources(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_reversals'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_reversal_entries'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_reversal_events'));
        self::assertTrue(Schema::hasColumns('financial_settlement_accounting_posting_reversals', [
            'owner_organization_id', 'financial_settlement_accounting_posting_execution_id', 'idempotency_key',
            'command_fingerprint', 'total_debit_minor', 'total_credit_minor', 'reason', 'revision', 'reversed_at',
        ]));
    }
}
