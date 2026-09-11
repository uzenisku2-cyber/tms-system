<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementStatementFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_tables_install_exact_source_linked_contracts(): void
    {
        self::assertTrue(Schema::hasColumns('financial_settlement_statements', [
            'public_id', 'owner_organization_id', 'recipient_type',
            'recipient_organization_id', 'recipient_driver_id', 'period_from', 'period_until',
            'currency', 'status', 'earning_amount_minor', 'deduction_amount_minor',
            'net_balance_minor', 'source_snapshot', 'idempotency_key',
            'command_fingerprint', 'revision', 'created_by_user_id',
            'billing_document_id', 'output_kind', 'output_direction', 'output_materialized_at',
        ]));
        self::assertTrue(Schema::hasColumns('financial_settlement_statement_lines', [
            'public_id', 'financial_settlement_statement_id', 'position',
            'source_type', 'source_public_id', 'source_revision',
            'financial_calculation_id', 'financial_mutual_charge_id',
            'effect', 'amount_minor', 'currency', 'source_snapshot',
        ]));
        self::assertTrue(Schema::hasColumns('financial_settlement_statement_events', [
            'public_id', 'financial_settlement_statement_id', 'revision', 'event_type',
            'idempotency_key', 'command_fingerprint', 'from_status', 'to_status',
            'reason', 'evidence', 'actor_user_id', 'occurred_at',
        ]));
    }
}
