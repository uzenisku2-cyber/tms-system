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
            substr_count(
                $source,
                'data-drayvia-page="settings"',
            ),
        );

        $this->assertStringContainsString(
            'const settings = () =>',
            $source
        );

        $this->assertStringContainsString(
            "event.target.closest('[data-drayvia-page]')",
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
