<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingCorrectionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_foundation_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_corrections'));
        self::assertTrue(Schema::hasTable('financial_settlement_accounting_posting_correction_events'));
        foreach (['public_id', 'owner_organization_id', 'original_execution_id', 'reversal_id', 'replacement_execution_id', 'idempotency_key', 'command_fingerprint', 'source_snapshot', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_accounting_posting_corrections', $column), $column);
        }
        foreach (['public_id', 'correction_id', 'event_type', 'payload', 'actor_user_id', 'occurred_at', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_accounting_posting_correction_events', $column), $column);
        }
    }
}
