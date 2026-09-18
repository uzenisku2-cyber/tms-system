<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use PHPUnit\Framework\TestCase;

final class BankTransactionEvidenceAdministrationContractTest extends TestCase
{
    public function test_administration_is_scoped_capacity_aware_linked_audited_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Fleet/Services/BankTransactionEvidenceAdministrationReadService.php');
        $controller = file_get_contents($root.'/app/Modules/Fleet/Controllers/BankTransactionEvidenceController.php');
        $routes = file_get_contents($root.'/app/Modules/Fleet/Routes/api.php');
        self::assertIsString($service);
        self::assertIsString($controller);
        self::assertIsString($routes);
        foreach ([
            "where('organization_context_id', \$organizationId)", 'compensation.view',
            'BankTransactionEvidenceCapacityService', 'allocated_amount_minor', 'remaining_capacity_amount_minor',
            'capacity_state', 'FinancialSettlementBankMatchCandidate', 'FinancialSettlementBankPayment',
            'FinancialSettlementBankPaymentReconciliation', 'SupplierFuelInvoiceBankPayment',
            'VehicleCostAllocationBankMatchingExecution', 'amountBreakdowns', 'events',
            "'read_only' => true", "'payment_mutated' => false", "'reconciliation_mutated' => false",
            "'accounting_posting_performed' => false",
        ] as $marker) {
            self::assertStringContainsString($marker, $service);
        }
        self::assertStringContainsString("BankTransactionEvidenceController::class, 'index'", $routes);
        self::assertStringContainsString("BankTransactionEvidenceController::class, 'show'", $routes);
        self::assertStringContainsString('BankTransactionEvidenceAdministrationReadService', $controller);
        self::assertStringNotContainsString('->save(', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('::create(', $service);
        self::assertStringNotContainsString('DB::transaction', $service);
    }
}
