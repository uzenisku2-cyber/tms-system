<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class BankStatementImportAdministrationUiTest extends TestCase
{
    public function test_bank_statement_import_workspace_exposes_upload_batches_rows_and_duplicate_review(): void
    {
        $this->get('/settings/bank-statement-imports')
            ->assertOk()
            ->assertSee('Bankovn&#237; importy', false)
            ->assertSee('id="upload"', false)
            ->assertSee('id="file"', false)
            ->assertSee("form.append('adapter','csob_csv')", false)
            ->assertSee('/api/v1/bank-statement-imports', false)
            ->assertSee('id="batches"', false)
            ->assertSee('id="detail"', false)
            ->assertSee('duplicate_candidate_row_count', false)
            ->assertSee('duplicate_candidates', false)
            ->assertSee('validation_messages', false)
            ->assertSee('source_row', false)
            ->assertSee('normalized_payload', false)
            ->assertDontSee('raw_payload', false)
            ->assertDontSee('VehicleCostAllocationBankMatchingExecution', false)
            ->assertDontSee('payment_id', false)
            ->assertDontSee('markAsPaid', false);
    }

    public function test_bank_page_embeds_bank_statement_import_workspace_inside_the_application_shell(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));
        self::assertIsString($source);
        self::assertStringContainsString('id="drayviaBankWorkspaceFrame"', $source);
        self::assertStringContainsString('src="/settings/bank-statement-imports"', $source);
        self::assertStringContainsString('bindBankWorkspace', $source);
        self::assertStringContainsString("if (page === 'bank')", $source);
        self::assertStringContainsString('OBNOVIT BANKU', $source);
        self::assertStringNotContainsString('Zde budeme resit pouze polozky', $source);
    }
}
