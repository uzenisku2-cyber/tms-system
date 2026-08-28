<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\PriceListConditionalRuleRewardComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class PriceListConditionalRuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof PriceListConditionalRule) {
            throw new LogicException(
                'Conditional rule resource requires a conditional rule model.',
            );
        }

        $rule = $this->resource;

        $rule->loadMissing([
            'metricComponents',
            'rewardComponents',
            'bands',
        ]);

        $numeratorSources = [];
        $denominatorSources = [];

        foreach ($rule->metricComponents as $component) {
            if (
                ! $component instanceof PriceListConditionalRuleMetricComponent
            ) {
                continue;
            }

            $source = (string) $component->getAttribute(
                'metric_source',
            );

            if (
                $component->getAttribute('component_role') ===
                    PriceListConditionalRuleMetricComponent::ROLE_NUMERATOR
            ) {
                $numeratorSources[] = $source;

                continue;
            }

            if (
                $component->getAttribute('component_role') ===
                    PriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR
            ) {
                $denominatorSources[] = $source;
            }
        }

        if ($rule->metricComponents->isEmpty()) {
            $legacyNumerator = $rule->getAttribute(
                'metric_numerator_source',
            );
            $legacyDenominator = $rule->getAttribute(
                'metric_denominator_source',
            );

            if (is_string($legacyNumerator)) {
                $numeratorSources[] = $legacyNumerator;
            }

            if (is_string($legacyDenominator)) {
                $denominatorSources[] = $legacyDenominator;
            }
        }

        $rewardQuantitySources = $rule->rewardComponents
            ->filter(
                static fn (mixed $component): bool =>
                    $component instanceof PriceListConditionalRuleRewardComponent,
            )
            ->pluck('metric_source')
            ->filter(static fn (mixed $source): bool => is_string($source))
            ->values()
            ->all();

        if ($rewardQuantitySources === []) {
            $legacyRewardQuantitySource = $rule->getAttribute(
                'reward_quantity_source',
            );

            if (is_string($legacyRewardQuantitySource)) {
                $rewardQuantitySources[] = $legacyRewardQuantitySource;
            }
        }

        return [
            'code' => (string) $rule->getAttribute('code'),
            'name' => (string) $rule->getAttribute('name'),
            'description' => $rule->getAttribute('description'),
            'metric_type' => (string) $rule->getAttribute(
                'metric_type',
            ),
            'metric_numerator_sources' => $numeratorSources,
            'metric_denominator_sources' => $denominatorSources,
            'metric_components' => PriceListConditionalRuleMetricComponentResource::collection(
                $rule->metricComponents,
            ),
            'evaluation_scope' => (string) $rule->getAttribute(
                'evaluation_scope',
            ),
            'reward_method' => (string) $rule->getAttribute(
                'reward_method',
            ),
            'reward_quantity_source' => $rule->getAttribute(
                'reward_quantity_source',
            ),
            'reward_quantity_sources' => $rewardQuantitySources,
            'reward_target_item_code' => $rule->getAttribute(
                'reward_target_item_code',
            ),
            'rounding_scale' => (int) $rule->getAttribute(
                'rounding_scale',
            ),
            'rounding_method' => (string) $rule->getAttribute(
                'rounding_method',
            ),
            'position' => (int) $rule->getAttribute('position'),
            'bands' => PriceListConditionalBandResource::collection(
                $rule->bands,
            ),
        ];
    }
}
