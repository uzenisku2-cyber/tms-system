<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DriverSurnameFirstPresentationTest extends TestCase
{
    public function test_driver_list_displays_surname_before_first_name(): void
    {
        $this->get('/carriers')
            ->assertOk()
            ->assertSee(
                '[driver.last_name, driver.first_name]',
                false,
            )
            ->assertSee(
                '.filter(Boolean)',
                false,
            )
            ->assertSee(
                ".join(' ')",
                false,
            );
    }
}
