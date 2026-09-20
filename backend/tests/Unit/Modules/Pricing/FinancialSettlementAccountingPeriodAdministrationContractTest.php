<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class FinancialSettlementAccountingPeriodAdministrationContractTest extends TestCase
{
    public function test_administration_is_scoped_filterable_auditable_and_read_only(): void
    {
        $backend = dirname(__DIR__, 4);
        $service = file_get_contents($backend.'/app/Modules/Pricing/Services/FinancialSettlementAccountingPeriodAdministrationReadService.php');
        $request = file_get_contents($backend.'/app/Modules/Pricing/Requests/IndexFinancialSettlementAccountingPeriodAdministrationRequest.php');
        $partial = file_get_contents($backend.'/resources/views/mvp/partials/financial-settlement-accounting-period-administration.blade.php');

        self::assertIsString($service);
        self::assertIsString($request);
        self::assertIsString($partial);
        self::assertStringContainsString("where('owner_organization_id', \$organizationId)", $service);
        self::assertStringContainsString("whereDate('period_end', '>=', \$value)", $service);
        self::assertStringContainsString("whereDate('period_start', '<=', \$value)", $service);
        self::assertStringContainsString("with('events')", $service);
        self::assertStringContainsString("can('compensation.view')", $request);
        self::assertStringContainsString("Rule::in(['open', 'closed', 'reopened'])", $request);
        self::assertStringContainsString("method:'POST'", $partial);
        self::assertStringContainsString('expected_revision', $partial);
        self::assertStringNotContainsString('->save(', $service);
        self::assertStringNotContainsString('->update(', $service);
        self::assertStringNotContainsString('->delete(', $service);
    }
}
