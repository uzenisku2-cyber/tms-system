<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class RouteWholeKilometreEntryTest extends TestCase
{
    public function test_route_kilometres_are_entered_as_whole_numbers(): void
    {
        $response = $this->get('/app');

        $response
            ->assertOk()
            ->assertSee(
                "key === 'actual_km'",
                false,
            )
            ->assertSee(
                "key === 'planned_km'",
                false,
            )
            ->assertSee(
                "input.step = '1';",
                false,
            )
            ->assertSee(
                "input.inputMode = 'numeric';",
                false,
            )
            ->assertSee(
                "if (key === 'surcharge_amount')",
                false,
            )
            ->assertSee(
                "input.step = '0.01';",
                false,
            )
            ->assertSee(
                'Number.isInteger(numericValue)',
                false,
            );
    }
}
