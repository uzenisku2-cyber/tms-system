<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FinancialMutualChargeAdministrationUiTest extends TestCase
{
    public function test_mutual_charge_administration_exposes_audited_lifecycle_controls(): void
    {
        $this->get('/settings/financial-mutual-charges')
            ->assertOk()
            ->assertSee('Vz&#225;jemn&#233; n&#225;klady', false)
            ->assertSee('/api/v1/financial-mutual-charges?', false)
            ->assertSee('data.items', false)
            ->assertSee('amount_minor', false)
            ->assertSee('visibility_status', false)
            ->assertSee('id="detailModal"', false)
            ->assertSee('source_snapshot', false)
            ->assertSee('event.event_type', false)
            ->assertSee('id="lifecycleActions"', false)
            ->assertSee('expected_revision', false)
            ->assertSee('crypto.randomUUID()', false)
            ->assertSee("actions.push(['confirm'", false)
            ->assertSee("actions.push(['dispute'", false)
            ->assertSee("actions.push(['reverse'", false);

        $source = file_get_contents(resource_path('views/mvp/financial-mutual-charges.blade.php'));
        self::assertIsString($source);
        foreach (['/materialize', '/payments', '/bank-matching', 'payment_marked:true'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function test_settings_links_to_mutual_charge_administration(): void
    {
        $this->get('/settings')
            ->assertOk()
            ->assertSee('/settings/financial-mutual-charges', false)
            ->assertSee('settings-financial-mutual-charges', false);
    }
}
