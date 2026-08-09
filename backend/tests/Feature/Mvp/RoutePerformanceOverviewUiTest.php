<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class RoutePerformanceOverviewUiTest extends TestCase
{
    public function test_route_overview_exposes_operational_kpis_and_limit_navigation(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Naloženo')
            ->assertSee('Doručeno na adresu')
            ->assertSee('Výdejní místo')
            ->assertSee('Odmítnuto zákazníkem')
            ->assertSee('Nedoručeno')
            ->assertSee('📅 Vlastní období')
            ->assertSee('Zrušit filtry')
            ->assertSee('Upravit zapsané údaje')
            ->assertSee('Opravit zapsané údaje')
            ->assertDontSee('Nastavení limitů')
            ->assertSee(
                'createPerformanceCell',
                false,
            )
            ->assertSee(
                'loadCompleteRouteHistory',
                false,
            )
            ->assertSee(
                'S020-04E3D5 ROUTE LIST READABILITY',
                false,
            )
            ->assertSee(
                'clearFiltersButton.disabled',
                false,
            )
            ->assertSee(
                'Období: Všechny trasy',
                false,
            )
            ->assertSee(
                'redirected_max_percent',
                false,
            )
            ->assertSee(
                'kilometre_deviation_max_percent',
                false,
            )
            ->assertSee(
                'performance-warning',
                false,
            )
            ->assertSee(
                'performance-critical',
                false,
            );
    }

    public function test_standalone_performance_settings_page_is_not_exposed(): void
    {
        $this->get('/performance-settings')
            ->assertNotFound();
    }
}
