<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BankStatementImportDuplicateResolutionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_append_only_resolution_table_has_no_financial_execution_columns(): void
    {
        self::assertTrue(Schema::hasTable('bank_statement_import_duplicate_resolutions'));
        self::assertTrue(Schema::hasColumns('bank_statement_import_duplicate_resolutions', ['public_id', 'organization_context_id', 'bank_statement_import_duplicate_candidate_id', 'idempotency_key', 'decision', 'reason', 'resolved_by_user_id', 'resolved_at']));
        foreach (['payment_id', 'billing_document_id', 'bank_matching_execution_id'] as $column) {
            self::assertFalse(Schema::hasColumn('bank_statement_import_duplicate_resolutions', $column));
        }
    }
}
