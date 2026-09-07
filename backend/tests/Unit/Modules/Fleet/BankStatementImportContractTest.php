<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class BankStatementImportContractTest extends TestCase
{
    public function test_import_is_configurable_traceable_duplicate_aware_and_non_matching(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/BankStatementImportService.php');
        $migration = file_get_contents($root.'/database/migrations/2026_09_10_000000_create_bank_statement_import_foundation.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertIsString($routes);
        foreach (['raw_payload', 'transaction_fingerprint', 'duplicate_candidate', "'source_type' => 'bank_import'", 'BankTransactionEvidenceService'] as $required) {
            self::assertStringContainsString($required, $service.$migration);
        }
        foreach (['bank-statement-imports.index', 'bank-statement-imports.store', 'bank-statement-imports.show'] as $route) {
            self::assertStringContainsString($route, $routes);
        }
        foreach (['VehicleCostAllocationBankMatchingExecution::query()', 'BillingDocument::query()', 'payment_id', 'billing_document_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
