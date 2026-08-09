<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class RouteSignedKilometreDifferenceTest extends TestCase
{
    public function test_route_overview_uses_signed_kilometre_difference_and_threshold_colors(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Rozdíl nájezdu')
            ->assertSee(
                'routeKilometreDifferencePresentation',
                false,
            )
            ->assertSee(
                'differenceKm / plannedKm * 100',
                false,
            )
            ->assertSee(
                "percentage >= 0",
                false,
            )
            ->assertSee(
                "Math.abs(percentage).toFixed(2)",
                false,
            )
            ->assertSee(
                'kilometre_deviation_max_percent',
                false,
            )
            ->assertSee(
                'performanceSeverityClass',
                false,
            )
            ->assertSee(
                'kilometre-difference-alert',
                false,
            )
            ->assertSee(
                'Nelze vypočítat',
                false,
            )
            ->assertDontSee('<th>Odchylka</th>', false);
    }
}