<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class PriceListConditionalRulePersistenceContractTest extends TestCase
{
    public function test_write_service_replaces_the_complete_rule_tree_atomically(): void
    {
        $backend = dirname(__DIR__, 4);
        $service = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/PriceListWriteService.php',
        );

        self::assertIsString($service);

        foreach ([
            'DB::transaction',
            'array_key_exists(',
            "'conditional_rules'",
            'replaceConditionalRules',
            'persistConditionalRules',
            'metricComponents()->delete()',
            'bands()->delete()',
            'conditionalRules()->create',
            'metricComponents()->create',
            'bands()->create',
        ] as $marker) {
            self::assertStringContainsString($marker, $service);
        }

        $bandDelete = strpos($service, 'bands()->delete()');
        $componentDelete = strpos(
            $service,
            'metricComponents()->delete()',
        );
        $ruleDelete = strpos($service, '$rule->delete()');

        self::assertIsInt($bandDelete);
        self::assertIsInt($componentDelete);
        self::assertIsInt($ruleDelete);
        self::assertLessThan($ruleDelete, $bandDelete);
        self::assertLessThan($ruleDelete, $componentDelete);
    }

    public function test_version_resource_exposes_rules_components_and_bands(): void
    {
        $backend = dirname(__DIR__, 4);
        $versionResource = file_get_contents(
            $backend.'/app/Modules/Pricing/Resources/PriceListVersionResource.php',
        );
        $ruleResource = file_get_contents(
            $backend.'/app/Modules/Pricing/Resources/PriceListConditionalRuleResource.php',
        );

        self::assertIsString($versionResource);
        self::assertIsString($ruleResource);
        self::assertStringContainsString(
            "'conditional_rules'",
            $versionResource,
        );
        self::assertStringContainsString(
            "'metric_numerator_sources'",
            $ruleResource,
        );
        self::assertStringContainsString(
            "'metric_denominator_sources'",
            $ruleResource,
        );
        self::assertStringContainsString("'bands'", $ruleResource);
    }

    public function test_new_version_copies_rules_without_copying_base_items(): void
    {
        $backend = dirname(__DIR__, 4);
        $service = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/PriceListWriteService.php',
        );

        self::assertIsString($service);
        self::assertStringContainsString(
            'copyConditionalRules',
            $service,
        );
        self::assertStringContainsString(
            '$this->copyConditionalRules(',
            $service,
        );
        self::assertStringNotContainsString(
            '$target->items()->create',
            $service,
        );
    }
}
