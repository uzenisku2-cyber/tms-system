<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DailyReportRouteEntryPresentationTest extends TestCase
{
    public function test_route_entry_language_layout_and_whole_kilometre_overview_are_present(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Uložit trasu')
            ->assertSee('Trasa byla uložena.')
            ->assertSee(
                '#dailyReportCreatePanel .daily-entry-grid',
                false,
            )
            ->assertSee(
                'max-width: 520px;',
                false,
            )
            ->assertSee(
                'const formatWholeKilometres = (value) =>',
                false,
            )
            ->assertSee(
                'Math.round(numericValue)',
                false,
            )
            ->assertSee(
                'formatWholeKilometres(item.planned_km)',
                false,
            )
            ->assertSee(
                'formatWholeKilometres(item.actual_km)',
                false,
            )
            ->assertDontSee('Uložit koncept');
    }
}