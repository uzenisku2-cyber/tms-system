<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class RouteWorkflowEditingUiTest extends TestCase
{
    public function test_route_page_uses_business_language_statuses_and_edit_actions(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Trasy')
            ->assertSee('+ Zapsat trasu')
            ->assertSee('Zapsané trasy')
            ->assertSee('ID řidiče')
            ->assertSee('Zapsáno řidičem')
            ->assertSee('Čeká na schválení')
            ->assertSee('Vyžaduje opravu')
            ->assertSee('Schváleno')
            ->assertSee('Upravit zapsané údaje')
            ->assertSee('Opravit zapsané údaje')
            ->assertSee('Odeslat ke schválení')
            ->assertSee(
                'performed_by_driver_external_id',
                false,
            )
            ->assertSee(
                "item.status === 'draft'",
                false,
            )
            ->assertSee(
                "item.status === 'correction_requested'",
                false,
            )
            ->assertSee(
                "method: 'PATCH'",
                false,
            )
            ->assertSee(
                '/correct',
                false,
            )
            ->assertSee(
                '/resubmit',
                false,
            )
            ->assertSee(
                'expected_version',
                false,
            );
    }
}