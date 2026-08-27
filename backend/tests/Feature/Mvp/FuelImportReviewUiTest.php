<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FuelImportReviewUiTest extends TestCase
{
    public function test_fuel_import_review_page_exposes_review_and_audit_contract(): void
    {
        $response = $this->get('/settings/fuel-imports');

        $response
            ->assertOk()
            ->assertSee('Importy paliva')
            ->assertSee('ORLEN')
            ->assertSee('MOL')
            ->assertSee('Historie revizí')
            ->assertSee('effective_payload', false)
            ->assertSee('/api/v1/fuel-imports', false)
            ->assertSee('rows/${n}', false)
            ->assertSee('/corrections', false)
            ->assertSee('tms_mvp_token', false)
            ->assertSee('X-Organization-ID', false);
    }

    public function test_settings_page_links_to_fuel_import_review(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('Importy paliva')
            ->assertSee('/settings/fuel-imports', false)
            ->assertSee('settings-fuel-imports', false);
    }
}
