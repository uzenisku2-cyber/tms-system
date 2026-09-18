<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementBankPaymentReconciliationExportContractTest extends TestCase
{
    public function test_export_is_scoped_filtered_business_readable_and_non_mutating(): void
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root.'/app/Modules/Pricing/Services/FinancialSettlementBankPaymentReconciliationCsvExportService.php');
        $controller = file_get_contents($root.'/app/Modules/Pricing/Controllers/FinancialSettlementAdministrationReadController.php');
        $routes = file_get_contents($root.'/app/Modules/Pricing/Routes/api.php');

        self::assertIsString($service);
        self::assertIsString($controller);
        self::assertIsString($routes);
        self::assertStringContainsString("where('owner_organization_id', \$organizationId)", $service);
        foreach (['status', 'party_type', 'direction', 'period_from', 'period_until'] as $filter) {
            self::assertStringContainsString("\$filters['{$filter}']", $service);
        }
        self::assertStringContainsString('lazyById(200)', $service);
        self::assertStringContainsString("fputcsv(\$output, \$row, ';'", $service);
        self::assertStringContainsString('FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED', $service);
        self::assertStringContainsString('response()->streamDownload', $controller);
        self::assertStringContainsString('financial-settlement-statements/reconciliation-export', $routes);
        self::assertStringContainsString("'perm:compensation.view'", $routes);
        foreach (['->create(', '->update(', '->delete(', '->save(', '->forceFill(', 'AccountingEntry', 'LedgerEntry'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
