<?php

namespace Tests\Feature\Mvp;

use Tests\TestCase;

class SettingsCatalogsUiTest extends TestCase
{
    public function test_settings_home_exposes_catalogs_and_route_settings(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('Nastavení')
            ->assertSee('Číselníky')
            ->assertSee('Nastavení tras');
    }

    public function test_catalogs_home_exposes_initial_catalogs(): void
    {
        $this->get('/settings/catalogs')
            ->assertOk()
            ->assertSee('Trasy')
            ->assertSee('Charakter tras')
            ->assertSee('Provozní důvody');
    }

    public function test_route_catalog_preserves_historical_route_identity_concept(): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_08_09_012700_create_route_catalog_foundation_tables.php',
            '--force' => true,
        ])->assertExitCode(0);

        $this->get('/settings/catalogs/routes')
            ->assertOk()
            ->assertSee('Číslo, název a oblast jsou historicky verzované údaje.')
            ->assertSee('Úprava těchto hodnot nikdy nepřepisuje starou verzi trasy.')
            ->assertSee('Zatím nejsou založené žádné trasy.');
    }

    public function test_route_character_is_separate_from_difficulty(): void
    {
        $this->get('/settings/catalogs/route-characters')
            ->assertOk()
            ->assertSee('Městská')
            ->assertSee('Venkovská')
            ->assertSee('Smíšená');

        $this->get('/settings/routes')
            ->assertOk()
            ->assertSee('Obtížnost trasy')
            ->assertSee('Tolerance km');
    }

    public function test_operational_reason_catalog_exposes_initial_reason_types(): void
    {
        $this->get('/settings/catalogs/operational-reasons')
            ->assertOk()
            ->assertSee('Odvod hotovosti')
            ->assertSee('Objížďka')
            ->assertSee('Výdejní místo');
    }
}
