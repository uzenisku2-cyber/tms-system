<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class DriverPriceListSurchargeUsabilityContractTest extends TestCase
{
    public function test_all_price_list_editors_expose_business_first_contract(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 4).'/resources/views/mvp/app.blade.php',
        );

        self::assertIsString($source);

        foreach ([
            'Za kter\u00e9 v\u00fdkony p\u0159\u00edplatek vyplatit',
            'data-conditional-rule-reward-quantities',
            'data-conditional-reward-source',
            'reward_quantity_sources:',
            'updateFinanceConditionalRuleSummary',
            'P\u0159i v\u00fdsledku',
            "rewardMethod.value = 'amount_per_unit'",
            'rewardMethodField.hidden = true',
            'rewardSources: [',
            '[data-monthly-price-list-scope]',
            '[data-monthly-driver-scope]',
            'const rewardQuantitySources = Array.from(',
            "const payoutBlock = payoutLegend?.closest('fieldset')",
            '<strong>Rozsah podm\u00ednky</strong>',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_all_price_lists_use_the_optional_guided_wizard(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 4).'/resources/views/mvp/app.blade.php',
        );

        self::assertIsString($source);

        foreach ([
            'S039-04A-R8-SURCHARGE-WIZARD',
            'Jaký příplatek chcete založit?',
            'Za jaké období podmínku vyhodnotit?',
            'Každou trasu samostatně',
            'Souhrnně za celý kalendářní měsíc',
            'Do uzavření měsíce je výsledek předběžný',
            'Přednastavené jsou doručené a přesměrované zásilky',
            'Sazba příplatku za jednotku${withoutVat ? \' bez DPH\' : \'\'} (Kč)',
            'data-s039-r8-summary-wrap',
            '+ Přidat příplatek',
            'Hodnota 0 Kč je platná',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_custom_rule_keeps_advanced_reward_methods(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 4).'/resources/views/mvp/app.blade.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '<option value="fixed_amount">',
            $source,
        );
        self::assertStringContainsString(
            '<option value="amount_per_unit">',
            $source,
        );
        self::assertStringContainsString(
            '<option value="percentage_of_item">',
            $source,
        );
        self::assertStringContainsString(
            "presetName !== 'custom'",
            $source,
        );
    }
}
