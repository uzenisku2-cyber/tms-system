<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class DriverConditionalPriceListPersistenceContractTest extends TestCase
{
    public function test_driver_write_service_owns_the_complete_conditional_rule_lifecycle(): void
    {
        $source = $this->serviceSource();

        foreach ([
            'ConditionalPriceListRulePayload $conditionalRulePayload',
            'DriverPriceListConditionalRule',
            'DriverPriceListConditionalRuleMetricComponent',
            'array_key_exists(',
            "'conditional_rules'",
            'persistConditionalRules',
            'replaceConditionalRules',
            'copyConditionalRules',
            'assertApprovableConditionalRules',
            'conditionalRuleInputFromVersion',
            'metricComponents()->create',
            'rewardComponents()->create',
            'bands()->create',
            'metricComponents()->delete',
            'rewardComponents()->delete',
            'bands()->delete',
            '$this->versionRelations()',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'private function persistConditionalRules(',
            ),
        );

        self::assertSame(
            1,
            substr_count(
                $source,
                'private function replaceConditionalRules(',
            ),
        );

        self::assertSame(
            1,
            substr_count(
                $source,
                'private function copyConditionalRules(',
            ),
        );

        self::assertSame(
            1,
            substr_count(
                $source,
                'private function assertApprovableConditionalRules(',
            ),
        );
    }

    public function test_create_copy_update_and_approval_call_the_expected_rule_operations(): void
    {
        $source = $this->serviceSource();

        $create = $this->methodBlock(
            $source,
            '    public function createDraft(',
            '    public function createDraftVersion(',
        );

        self::assertStringContainsString(
            '$this->conditionalRulePayload->fromInput($data)',
            $create,
        );

        self::assertStringContainsString(
            '$this->persistConditionalRules(',
            $create,
        );

        $newVersion = $this->methodBlock(
            $source,
            '    public function createDraftVersion(',
            '    public function updateDraftVersion(',
        );

        self::assertStringContainsString(
            '$replaceConditionalRules',
            $newVersion,
        );

        self::assertStringContainsString(
            '$this->persistConditionalRules(',
            $newVersion,
        );

        self::assertStringContainsString(
            '$this->copyConditionalRules(',
            $newVersion,
        );

        $update = $this->methodBlock(
            $source,
            '    public function updateDraftVersion(',
            '    public function approveDraftVersion(',
        );

        self::assertStringContainsString(
            '$replaceConditionalRules',
            $update,
        );

        self::assertStringContainsString(
            '$this->replaceConditionalRules(',
            $update,
        );

        $approval = $this->methodBlock(
            $source,
            '    public function approveDraftVersion(',
            '    public function activateApprovedVersion(',
        );

        self::assertStringContainsString(
            '$this->assertApprovableConditionalRules(',
            $approval,
        );
    }

    public function test_children_are_removed_before_their_parent_rule(): void
    {
        $source = $this->serviceSource();

        $replace = $this->methodBlock(
            $source,
            '    private function replaceConditionalRules(',
            '    private function copyConditionalRules(',
        );

        $bandDelete = strpos(
            $replace,
            '$rule->bands()->delete();',
        );

        $componentDelete = strpos(
            $replace,
            '$rule->metricComponents()->delete();',
        );

        $ruleDelete = strpos(
            $replace,
            '$rule->delete();',
        );

        self::assertIsInt($bandDelete);
        self::assertIsInt($componentDelete);
        self::assertIsInt($ruleDelete);
        self::assertLessThan($ruleDelete, $bandDelete);
        self::assertLessThan($ruleDelete, $componentDelete);
    }

    private function serviceSource(): string
    {
        $backend = dirname(__DIR__, 4);

        $source = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/DriverPriceListWriteService.php',
        );

        self::assertIsString($source);

        return $source;
    }

    private function methodBlock(
        string $source,
        string $startMarker,
        string $endMarker,
    ): string {
        $start = strpos($source, $startMarker);

        self::assertIsInt($start);

        $end = strpos(
            $source,
            $endMarker,
            $start + strlen($startMarker),
        );

        self::assertIsInt($end);

        return substr(
            $source,
            $start,
            $end - $start,
        );
    }
}
