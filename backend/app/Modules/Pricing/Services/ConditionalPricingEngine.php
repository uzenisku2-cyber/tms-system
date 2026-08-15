<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\PriceListConditionalRule;

final class ConditionalPricingEngine
{
    public function __construct(
        private readonly ConditionalPricingScopeAggregator $scopeAggregator,
        private readonly ConditionalPricingRuleEvaluator $ruleEvaluator,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $snapshots
     * @param  array<string, int|float|string>  $baseItemAmounts
     * @return array{
     *     aggregate: array<string, mixed>,
     *     evaluation: array<string, mixed>
     * }
     */
    public function evaluateRule(
        PriceListConditionalRule $rule,
        array $snapshots,
        array $baseItemAmounts = [],
    ): array {
        $aggregate =
            $this->scopeAggregator->aggregate(
                $rule,
                $snapshots,
            );

        $evaluation =
            $this->ruleEvaluator->evaluate(
                $rule,
                $aggregate,
                $baseItemAmounts,
            );

        return [
            'aggregate' => $aggregate,
            'evaluation' => $evaluation,
        ];
    }
}
