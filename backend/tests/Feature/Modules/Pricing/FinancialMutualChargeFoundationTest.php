<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialMutualChargeFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_mutual_charge_tables_install_the_bilateral_exact_cent_contract(): void
    {
        self::assertTrue(Schema::hasColumns('financial_mutual_charges', [
            'public_id',
            'owner_organization_id',
            'counterparty_organization_id',
            'counterparty_driver_id',
            'counterparty_type',
            'direction',
            'category',
            'service_period_from',
            'service_period_until',
            'amount_minor',
            'currency',
            'vat_treatment',
            'offset_eligible',
            'status',
            'visibility_status',
            'source_type',
            'source_public_id',
            'source_snapshot',
            'idempotency_key',
            'command_fingerprint',
            'revision',
            'confirmed_at',
            'shared_at',
            'reversed_at',
        ]));

        self::assertTrue(Schema::hasColumns('financial_mutual_charge_events', [
            'public_id',
            'financial_mutual_charge_id',
            'revision',
            'event_type',
            'from_status',
            'to_status',
            'reason',
            'evidence',
            'actor_user_id',
            'occurred_at',
        ]));
    }
}
