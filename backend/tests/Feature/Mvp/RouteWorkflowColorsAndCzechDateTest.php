<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class RouteWorkflowColorsAndCzechDateTest extends TestCase
{
    public function test_route_ui_uses_workflow_colors_and_czech_date_presentation(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee(
                'formatCzechDate',
                false,
            )
            ->assertSee(
                '`${day}.${month}.${year}`',
                false,
            )
            ->assertSee(
                'formatCzechDate(item.service_date)',
                false,
            )
            ->assertSee(
                'route-action-positive',
                false,
            )
            ->assertSee(
                'route-action-correction',
                false,
            )
            ->assertSee(
                '.route-filter-chip.route-status-waiting',
                false,
            )
            ->assertSee(
                '.route-filter-chip.route-status-correction',
                false,
            )
            ->assertSee(
                '#fffaeb',
                false,
            )
            ->assertSee(
                '#fef3f2',
                false,
            )
            ->assertSee(
                '#ecfdf3',
                false,
            );
    }
}
