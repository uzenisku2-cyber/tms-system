<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FuelImportReviewUiTest extends TestCase
{
    public function test_fuel_import_review_page_exposes_review_audit_and_finalization_contract(): void
    {
        $this->get('/settings/fuel-imports')
            ->assertOk()
            ->assertSee('Importy paliva')
            ->assertSee('ORLEN')
            ->assertSee('MOL')
            ->assertSee('id="history"', false)
            ->assertSee('effective_payload', false)
            ->assertSee('/api/v1/fuel-imports', false)
            ->assertSee('rows/${n}', false)
            ->assertSee('/corrections', false)
            ->assertSee('/finalization', false)
            ->assertSee('expected_correction_revision', false)
            ->assertSee('id="finalize"', false)
            ->assertSee('id="finalReason"', false)
            ->assertSee('transaction_public_id', false)
            ->assertSee('Kdo tankoval')
            ->assertSee('id="actualDriver"', false)
            ->assertSee('id="driverReason"', false)
            ->assertSee('id="driverAttributionHistory"', false)
            ->assertSee('/api/v1/fuel-transactions/', false)
            ->assertSee('/driver-attribution', false)
            ->assertSee('/eligible-drivers', false)
            ->assertSee('/driver-attributions', false)
            ->assertSee('expected_revision', false)
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
