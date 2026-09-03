<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FuelTransactionAdministrationUiTest extends TestCase
{
    public function test_business_overview_exposes_filters_table_and_driver_attribution_without_raw_json(): void
    {
        $this->get('/settings/fuel-transactions')
            ->assertOk()
            ->assertSee('P&#345;ehled tankov&#225;n&#237;', false)
            ->assertSee('id="dateFrom"', false)
            ->assertSee('id="dateTo"', false)
            ->assertSee('id="provider"', false)
            ->assertSee('id="driver"', false)
            ->assertSee('id="card"', false)
            ->assertSee('id="search"', false)
            ->assertSee('id="reconciliationStatus"', false)
            ->assertSee('id="operationalOverview"', false)
            ->assertSee('id="overviewAttention"', false)
            ->assertSee('id="overviewProviders"', false)
            ->assertSee('id="overviewDrivers"', false)
            ->assertSee('id="overviewCurrencies"', false)
            ->assertSee('<h3>Finan&#269;n&#237; souhrn</h3>', false)
            ->assertDontSee('Finan&#269;n&#237; souhrn podle m&#283;ny', false)
            ->assertSee('Provozn&#237; p&#345;ehled shod', false)
            ->assertSee('Vy&#382;aduje pozornost', false)
            ->assertSee('/api/v1/fuel-transactions/overview?', false)
            ->assertSee('overviewQuery()', false)
            ->assertSee('id="reconciliationModal"', false)
            ->assertSee('id="evaluateReconciliation"', false)
            ->assertSee('id="decisionCode"', false)
            ->assertSee('id="dailyReportId"', false)
            ->assertSee('id="decisionHint"', false)
            ->assertSee('D&#367;vod rozhodnut&#237; (povinn&#233;)', false)
            ->assertSee('Vypl\u0148te povinn\u00fd d\u016fvod rozhodnut\u00ed', false)
            ->assertSee('Historie vyhodnocen\u00ed', false)
            ->assertSee('Nalezen\u00e9 denn\u00ed z\u00e1znamy', false)
            ->assertSee('/api/v1/fuel-transactions?', false)
            ->assertSee('/driver-attribution', false)
            ->assertSee('/eligible-drivers', false)
            ->assertSee('/driver-attributions', false)
            ->assertSee('/reconciliation/evaluate', false)
            ->assertSee('/reconciliation/decisions', false)
            ->assertSee('can_manage_reconciliation', false)
            ->assertSee('expected_revision', false)
            ->assertSee('/settings/fuel-imports', false)
            ->assertDontSee('raw_payload', false)
            ->assertDontSee('normalized_payload', false)
            ->assertDontSee('Opraven&#225; data (JSON)', false);
    }

    public function test_source_transaction_time_preserves_provider_wall_clock(): void
    {
        $source = file_get_contents(resource_path('views/mvp/fuel-transactions.blade.php'));
        self::assertIsString($source);
        self::assertStringContainsString('const sourceDate=', $source);
        self::assertStringContainsString('sourceDate(x.occurred_at)', $source);
        self::assertStringContainsString('const auditDate=', $source);
        self::assertStringContainsString('auditDate(x.corrected_at)', $source);
        self::assertStringNotContainsString("const date=v=>v?new Date(v).toLocaleString('cs-CZ')", $source);
    }

    public function test_main_fuel_workspace_opens_business_transaction_overview(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));
        self::assertIsString($source);
        self::assertSame(3, substr_count($source, '/settings/fuel-transactions'));
        self::assertSame(0, substr_count($source, "frame.src = '/settings/fuel-imports'"));
        $this->get('/settings/fuel-transactions')->assertOk();
    }
}
