<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BillingDocumentCommercialIdentityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commercial_identity_foundation_is_installed(): void
    {
        self::assertTrue(Schema::hasColumns('billing_document_commercial_identities', [
            'public_id', 'billing_document_id', 'direction', 'document_number',
            'variable_symbol', 'issued_on', 'taxable_supply_on', 'due_on',
            'counterparty_name', 'counterparty_snapshot', 'revision', 'created_by_user_id',
        ]));
        self::assertTrue(Schema::hasColumns('billing_document_commercial_identity_events', [
            'public_id', 'billing_document_commercial_identity_id', 'revision',
            'event_type', 'reason', 'evidence', 'occurred_at', 'actor_user_id',
        ]));
    }
}
