<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementAccountingPostingAdministrationContractTest extends TestCase
{
    public function test_administration_is_scoped_read_only_filterable_and_lifecycle_aware(): void
    {
        $backend = dirname(__DIR__, 4);
        $service = file_get_contents($backend.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPostingAdministrationReadService.php');
        $request = file_get_contents($backend.'/app/Modules/Pricing/Requests/IndexFinancialSettlementAccountingPostingAdministrationRequest.php');

        self::assertIsString($service);
        self::assertIsString($request);
        self::assertStringContainsString("where('owner_organization_id', \$organizationId)", $service);
        self::assertStringContainsString('FinancialSettlementAccountingPostingReversal::query()', $service);
        self::assertStringContainsString('FinancialSettlementAccountingPostingCorrection::query()', $service);
        self::assertStringContainsString("'entries'", $service);
        self::assertStringContainsString("'timeline'", $service);
        self::assertStringContainsString("can('compensation.view')", $request);
        self::assertStringNotContainsString('->save(', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
    }
}
