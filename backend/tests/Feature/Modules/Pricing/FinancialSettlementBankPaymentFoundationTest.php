<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class FinancialSettlementBankPaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_settlement_payment_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasTable('financial_settlement_bank_payments'));
        self::assertTrue(Schema::hasTable('financial_settlement_bank_payment_events'));
        foreach (['owner_organization_id', 'financial_settlement_bank_match_candidate_id', 'financial_settlement_statement_id', 'billing_document_id', 'bank_transaction_evidence_id', 'allocated_amount_minor', 'status', 'revision'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_bank_payments', $column));
        }
        foreach (['financial_settlement_bank_payment_id', 'revision', 'event_type', 'evidence', 'actor_user_id'] as $column) {
            self::assertTrue(Schema::hasColumn('financial_settlement_bank_payment_events', $column));
        }
    }
}
