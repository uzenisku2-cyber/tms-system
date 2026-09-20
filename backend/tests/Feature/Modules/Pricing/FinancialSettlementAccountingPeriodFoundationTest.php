<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementAccountingPeriodFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_period_foundation_is_installed(): void
    {
        self::assertTrue(Schema::hasColumns('financial_settlement_accounting_periods', ['public_id', 'owner_organization_id', 'period_start', 'period_end', 'currency', 'status', 'revision']));
        self::assertTrue(Schema::hasColumns('financial_settlement_accounting_period_events', ['accounting_period_id', 'event_type', 'idempotency_key', 'command_fingerprint', 'payload', 'revision']));
    }
}
