<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DailyReportEffectiveFormEntryUiTest extends TestCase
{
    public function test_mvp_daily_report_entry_uses_effective_configuration(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee(
                'dailyReportDynamicFields',
                false,
            )
            ->assertSee(
                'dailyReportFormConfigurationState',
                false,
            )
            ->assertSee(
                '/api/v1/daily-report-form/effective?service_date=',
                false,
            )
            ->assertSee(
                'effectiveDailyReportConfiguration',
                false,
            )
            ->assertSee(
                'custom_field_values',
                false,
            )
            ->assertSee(
                'buildDailyReportPayload',
                false,
            )
            ->assertSee(
                'actual_km_source',
                false,
            )
            ->assertSee(
                'Pro zadané datum není nastavena platná konfigurace denního výkazu.',
            )
            ->assertDontSee(
                'Přesměrované zásilky',
            )
            ->assertDontSee(
                'Nedoručené zásilky',
            );
    }
}