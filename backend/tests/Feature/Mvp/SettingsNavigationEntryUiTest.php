<?php

namespace Tests\Feature\Mvp;

use Tests\TestCase;

class SettingsNavigationEntryUiTest extends TestCase
{
    public function test_main_mvp_source_exposes_single_settings_navigation_entry(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        $this->assertIsString($source);

        $this->assertSame(
            1,
            substr_count($source, 'data-testid="management-settings-link"')
        );

        $this->assertStringContainsString(
            'href="/settings"',
            $source
        );

        $this->assertStringContainsString(
            '>Nastavení</a>',
            $source
        );
    }

    public function test_settings_entry_target_is_available(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('Číselníky')
            ->assertSee('Nastavení tras');
    }
}
