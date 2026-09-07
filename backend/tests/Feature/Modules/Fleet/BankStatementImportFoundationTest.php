<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BankStatementImportFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_statement_import_tables_are_installed_without_matching_or_payment_mutation(): void
    {
        foreach (['bank_statement_import_batches', 'bank_statement_import_rows', 'bank_statement_import_duplicate_candidates'] as $table) {
            self::assertTrue(Schema::hasTable($table));
        }
        self::assertTrue(Schema::hasColumns('bank_statement_import_batches', ['organization_context_id', 'idempotency_key', 'file_sha256', 'mapping', 'source_row_count']));
        self::assertTrue(Schema::hasColumns('bank_statement_import_rows', ['raw_payload', 'normalized_payload', 'transaction_fingerprint', 'bank_transaction_evidence_id']));
        self::assertFalse(Schema::hasColumn('bank_statement_import_rows', 'billing_document_id'));
        self::assertFalse(Schema::hasColumn('bank_statement_import_rows', 'payment_id'));
    }
}
