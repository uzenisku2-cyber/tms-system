<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DailyReportFormCustomizationUiTest extends TestCase
{
    public function test_settings_page_supports_version_copy_and_custom_fields(): void
    {
        $this->get('/daily-report-settings')
            ->assertOk()
            ->assertSee('+ Přidat položku')
            ->assertSee('Upravit / vytvořit novou verzi')
            ->assertSee('Název položky')
            ->assertSee('Typ hodnoty')
            ->assertSee('Částka Kč')
            ->assertSee('Ano / ne')
            ->assertSee('Upravit položku')
            ->assertSee('Odebrat');
    }
}