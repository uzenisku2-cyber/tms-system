<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class MvpLaunchUiTest extends TestCase
{
    public function test_root_serves_mvp_pilot_ui(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('TMS System')
            ->assertSee('MVP / Pilot Launch');
    }

    public function test_login_route_serves_mvp_pilot_ui(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Přihlášení');
    }

    public function test_app_route_serves_mvp_pilot_ui(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Zapsané trasy')
            ->assertSee('+ Zapsat trasu')
            ->assertSee('Uložit trasu')
            ->assertSee('Dopravci a řidiči')
            ->assertDontSee('Historický import')
            ->assertSee('/api/v1/daily-reports', false)
            ->assertSee('/api/v1/drivers', false)
            ->assertSee('X-Organization-ID', false);
    }
}
