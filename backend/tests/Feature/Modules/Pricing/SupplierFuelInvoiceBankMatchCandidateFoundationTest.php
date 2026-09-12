<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SupplierFuelInvoiceBankMatchCandidateFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_fuel_invoice_bank_match_candidate_tables_are_installed(): void
    {
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_bank_match_candidates', [
            'public_id', 'owner_organization_id', 'billing_document_id',
            'bank_transaction_evidence_id', 'bank_transaction_evidence_revision',
            'idempotency_key', 'candidate_fingerprint', 'currency',
            'bank_amount_minor', 'invoice_unpaid_amount_minor', 'proposed_amount_minor',
            'score_basis_points', 'status', 'match_reasons', 'source_snapshot',
            'revision', 'proposed_by_user_id', 'proposed_at', 'reviewed_by_user_id',
            'reviewed_at', 'review_reason',
        ]));
        self::assertTrue(Schema::hasColumns('supplier_fuel_invoice_bank_match_candidate_events', [
            'public_id', 'supplier_fuel_invoice_bank_match_candidate_id', 'revision',
            'event_type', 'idempotency_key', 'candidate_fingerprint', 'reason',
            'evidence', 'actor_user_id', 'occurred_at',
        ]));
    }
}
