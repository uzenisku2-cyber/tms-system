<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class BankStatementImportDuplicateResolutionContractTest extends TestCase
{
    public function test_resolution_is_audited_idempotent_and_non_matching(): void
    {
        $root = dirname(__DIR__, 4).'/app/Modules/Fleet/';
        $service = file_get_contents($root.'Services/BankStatementImportDuplicateResolutionService.php');
        $model = file_get_contents($root.'Models/BankStatementImportDuplicateResolution.php');
        $request = file_get_contents($root.'Requests/ResolveBankStatementImportDuplicateCandidateRequest.php');
        self::assertIsString($service);
        self::assertIsString($model);
        self::assertIsString($request);
        foreach (['idempotency_key', 'organization_context_id', 'reason', 'lockForUpdate'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        foreach (['confirmed_duplicate', 'dismissed'] as $decision) {
            self::assertStringContainsString($decision, $request);
        }foreach (['append-only', 'updating', 'deleting'] as $marker) {
            self::assertStringContainsString($marker, $model);
        }foreach (['BillingDocument', 'payment_id', 'markAsPaid', 'VehicleCostAllocationBankMatchingExecution'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
