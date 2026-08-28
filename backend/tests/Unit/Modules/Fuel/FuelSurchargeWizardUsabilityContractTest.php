<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FuelSurchargeWizardUsabilityContractTest extends TestCase
{
    #[Test]
    public function phm_exposes_optional_six_step_wizard_above_history(): void
    {
        $view = $this->source('resources/views/mvp/app.blade.php');
        $script = $this->source('public/assets/s039-fuel-surcharge.js');

        self::assertStringContainsString('data-fuel-surcharge-add', $view);
        self::assertStringContainsString('Historie palivových příplatků', $view);
        self::assertStringContainsString('Krok ${state.step} ze 6', $script);
        self::assertStringContainsString('state.step === 6', $script);
        self::assertStringContainsString('Bez přiřazených příjemců', $script);
    }

    #[Test]
    public function wizard_keeps_customer_and_recipient_rates_explicit(): void
    {
        $script = $this->source('public/assets/s039-fuel-surcharge.js');

        self::assertStringContainsString('billing_rate_per_actual_km', $script);
        self::assertStringContainsString('payout_rate_per_actual_km', $script);
        self::assertStringContainsString('Částku zadávejte bez DPH', $script);
        self::assertStringContainsString('Skutečné km × příslušná sazba', $script);
        self::assertStringContainsString("request('/fuel-surcharges'", $script);
    }

    #[Test]
    public function recipient_selection_is_individual_and_margin_is_internal(): void
    {
        $script = $this->source('public/assets/s039-fuel-surcharge.js');

        self::assertStringContainsString("'own_driver'", $script);
        self::assertStringContainsString("'external_carrier'", $script);
        self::assertStringContainsString('margin_per_actual_km', $script);
        self::assertStringContainsString('Interní marže', $script);
        self::assertStringContainsString('Není nutné vybrat nikoho', $script);
    }

    #[Test]
    public function czech_presentation_and_repeatable_preview_data_are_preserved(): void
    {
        $script = $this->source('public/assets/s039-fuel-surcharge.js');
        $seeder = $this->source('database/seeders/FuelSurchargePreviewSeeder.php');

        self::assertStringContainsString("active: 'Aktivní'", $script);
        self::assertStringContainsString('formatFuelDate(item.valid_from)', $script);
        self::assertStringContainsString('>Podrobnosti</button>', $script);
        self::assertStringContainsString('item?.external_carrier?.name', $script);
        self::assertStringContainsString('Zásilkovna – ukázkový odběratel', $seeder);
        self::assertStringContainsString('Dominik Náhled', $seeder);
        self::assertStringContainsString('Náhledový externí dopravce', $seeder);
        self::assertStringContainsString('S039-OWNER', $seeder);
        self::assertStringContainsString('insertGetId', $seeder);
        self::assertStringContainsString("'valid_until' => null", $seeder);
    }

    #[Test]
    public function organization_context_is_resolved_from_the_authenticated_user(): void
    {
        $view = $this->source('resources/views/mvp/app.blade.php');
        $script = $this->source('public/assets/s039-fuel-surcharge.js');
        $resource = $this->source('app/Modules/Identity/Resources/UserResource.php');

        self::assertStringContainsString("const organizationKey = 'tms_mvp_organization_id'", $view);
        self::assertStringContainsString('payload.user?.organizations', $view);
        self::assertStringNotContainsString("headers['X-Organization-ID'] = '1'", $view);
        self::assertStringContainsString("sessionStorage.getItem('tms_mvp_organization_id')", $script);
        self::assertStringNotContainsString("'X-Organization-ID': '1'", $script);
        self::assertStringContainsString("'organizations' => \$organizations", $resource);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(base_path($relativePath));
        self::assertIsString($source);
        return $source;
    }
}
