<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DailyReportFormSettingsUiTest extends TestCase
{
    public function test_daily_report_settings_page_contains_versioned_validity_controls(): void
    {
        $this->get('/daily-report-settings')
            ->assertOk()
            ->assertSee('Nastavení denního výkazu')
            ->assertSee('+ Nová verze nastavení')
            ->assertSee('Platnost od')
            ->assertSee('Platnost do')
            ->assertSee('Bez omezení')
            ->assertSee('Datum')
            ->assertSee('Trasa č.')
            ->assertSee('/api/v1/daily-report-form-configurations', false);
    }
}