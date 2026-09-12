<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SupplierFuelInvoiceBankPaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_fuel_invoice_bank_payment_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_bank_payments', [
            'public_id', 'owner_organization_id', 'billing_document_id',
            'bank_transaction_evidence_id', 'bank_transaction_evidence_revision',
            'idempotency_key', 'command_fingerprint', 'allocated_amount_minor',
            'currency', 'status', 'reason', 'matched_by_user_id', 'matched_at',
            'reversed_by_user_id', 'reversed_at', 'reversal_reason', 'revision',
        ]));
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_bank_payment_events', [
            'public_id', 'supplier_fuel_invoice_bank_payment_id', 'revision',
            'event_type', 'idempotency_key', 'command_fingerprint', 'reason',
            'evidence', 'actor_user_id', 'occurred_at',
        ]));
    }
}
