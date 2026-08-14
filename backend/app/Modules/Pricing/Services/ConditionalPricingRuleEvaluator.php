<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\PriceListConditionalBand;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use DomainException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

final class ConditionalPricingRuleEvaluator
{
    private const METRIC_SCALE = 6;

    private const CALCULATION_SCALE = 8;

    /**
     * @param array{
     *     evaluation_scope: string,
     *     driver_id: int,
     *     period: string,
     *     route_count: int,
     *     metric_numerator_source: string,
     *     metric_numerator_value: string,
     *     metric_denominator_source: string|null,
     *     metric_denominator_value: string|null,
     *     reward_quantity_source: string|null,
     *     reward_quantity_value: string|null
     * } $aggregate
     * @param  array<string, int|float|string>  $baseItemAmounts
     * @return array{
     *     metric_type: string,
     *     metric_value: string,
     *     matched_band_id: int|null,
     *     matched_band_position: int|null,
     *     adjustment_value: string|null,
     *     reward_method: string,
     *     reward_quantity_value: string|null,
     *     reward_target_item_code: string|null,
     *     reward_target_item_amount: string|null,
     *     conditional_amount: string,
     *     rounding_scale: int,
     *     rounding_method: string
     * }
     */
    public function evaluate(
        PriceListConditionalRule $rule,
        array $aggregate,
        array $baseItemAmounts = [],
    ): array {
        $this->assertAggregateMatchesRule(
            $rule,
            $aggregate,
        );

        $metricType = $this->requiredString(
            $rule,
            'metric_type',
        );

        if (
            ! in_array(
                $metricType,
                PriceListConditionalRule::METRIC_TYPES,
                true,
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Unsupported conditional metric type [%s].',
                    $metricType,
                ),
            );
        }

        $numerator = $this->decimal(
            $aggregate['metric_numerator_value'],
            'Metric numerator',
            self::METRIC_SCALE,
        );

        $metricValue = match ($metricType) {
            PriceListConditionalRule::METRIC_TYPE_QUANTITY => $this->quantityMetric(
                $numerator,
                $aggregate,
            ),

            PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE => $this->ratioMetric(
                $numerator,
                $aggregate,
            ),

            default => throw new LogicException(
                'Unsupported conditional metric type.',
            ),
        };

        $matchedBand = $this->matchedBand(
            $rule,
            $metricValue,
        );

        $rewardMethod = $this->requiredString(
            $rule,
            'reward_method',
        );

        if (
            ! in_array(
                $rewardMethod,
                PriceListConditionalRule::REWARD_METHODS,
                true,
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Unsupported conditional reward method [%s].',
                    $rewardMethod,
                ),
            );
        }

        $roundingScale = $this->roundingScale($rule);
        $roundingMethod = $this->requiredString(
            $rule,
            'rounding_method',
        );

        if (
            $roundingMethod
            !== PriceListConditionalRule::ROUNDING_METHOD_HALF_UP
        ) {
            throw new LogicException(
                sprintf(
                    'Unsupported conditional rounding method [%s].',
                    $roundingMethod,
                ),
            );
        }

        if ($matchedBand === null) {
            return [
                'metric_type' => $metricType,
                'metric_value' => $metricValue,
                'matched_band_id' => null,
                'matched_band_position' => null,
                'adjustment_value' => null,
                'reward_method' => $rewardMethod,
                'reward_quantity_value' => $aggregate['reward_quantity_value'],
                'reward_target_item_code' => $this->nullableString(
                    $rule,
                    'reward_target_item_code',
                ),
                'reward_target_item_amount' => null,
                'conditional_amount' => $this->zero($roundingScale),
                'rounding_scale' => $roundingScale,
                'rounding_method' => $roundingMethod,
            ];
        }

        $adjustmentValue = $this->decimal(
            $matchedBand->getAttribute('adjustment_value'),
            'Conditional adjustment value',
            self::CALCULATION_SCALE,
        );

        $rewardQuantityValue = null;
        $targetItemCode = null;
        $targetItemAmount = null;

        $unroundedAmount = match ($rewardMethod) {
            PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT => $this->amountPerUnit(
                $aggregate,
                $adjustmentValue,
                $rewardQuantityValue,
            ),

            PriceListConditionalRule::REWARD_METHOD_FIXED_AMOUNT => $adjustmentValue,

            PriceListConditionalRule::REWARD_METHOD_PERCENTAGE_OF_ITEM => $this->percentageOfItem(
                $rule,
                $baseItemAmounts,
                $adjustmentValue,
                $targetItemCode,
                $targetItemAmount,
            ),

            default => throw new LogicException(
                'Unsupported conditional reward method.',
            ),
        };

        return [
            'metric_type' => $metricType,
            'metric_value' => $metricValue,
            'matched_band_id' => $this->nullablePositiveId($matchedBand),
            'matched_band_position' => $this->positiveIntegerAttribute(
                $matchedBand,
                'position',
            ),
            'adjustment_value' => $this->normalizeOutputDecimal(
                $adjustmentValue,
            ),
            'reward_method' => $rewardMethod,
            'reward_quantity_value' => $rewardQuantityValue,
            'reward_target_item_code' => $targetItemCode,
            'reward_target_item_amount' => $targetItemAmount,
            'conditional_amount' => $this->roundHalfUp(
                $unroundedAmount,
                $roundingScale,
            ),
            'rounding_scale' => $roundingScale,
            'rounding_method' => $roundingMethod,
        ];
    }

    /**
     * @param  array<string, mixed>  $aggregate
     */
    private function quantityMetric(
        string $numerator,
        array $aggregate,
    ): string {
        if (
            $aggregate['metric_denominator_source'] !== null
            || $aggregate['metric_denominator_value'] !== null
        ) {
            throw new LogicException(
                'Quantity metric cannot contain a denominator.',
            );
        }

        return bcadd(
            $numerator,
            '0',
            self::METRIC_SCALE,
        );
    }

    /**
     * @param  array<string, mixed>  $aggregate
     */
    private function ratioMetric(
        string $numerator,
        array $aggregate,
    ): string {
        if (
            $aggregate['metric_denominator_source'] === null
            || $aggregate['metric_denominator_value'] === null
        ) {
            throw new LogicException(
                'Ratio metric requires an explicit denominator.',
            );
        }

        $denominator = $this->decimal(
            $aggregate['metric_denominator_value'],
            'Metric denominator',
            self::METRIC_SCALE,
        );

        if (
            bccomp(
                $denominator,
                '0',
                self::METRIC_SCALE,
            ) === 0
        ) {
            throw new DomainException(
                'Conditional ratio metric cannot be evaluated with a zero denominator.',
            );
        }

        $ratio = bcdiv(
            $numerator,
            $denominator,
            self::CALCULATION_SCALE,
        );

        return bcmul(
            $ratio,
            '100',
            self::METRIC_SCALE,
        );
    }

    private function matchedBand(
        PriceListConditionalRule $rule,
        string $metricValue,
    ): ?PriceListConditionalBand {
        /** @var Collection<int, PriceListConditionalBand> $bands */
        $bands = $rule->relationLoaded('bands')
            ? $rule->getRelation('bands')
            : $rule->bands()->get();

        $matches = $bands
            ->filter(
                fn (PriceListConditionalBand $band): bool => $this->bandContains(
                    $band,
                    $metricValue,
                ),
            )
            ->values();

        if ($matches->count() > 1) {
            throw new LogicException(
                'Conditional metric matched more than one band.',
            );
        }

        $band = $matches->first();

        return $band instanceof PriceListConditionalBand
            ? $band
            : null;
    }

    private function bandContains(
        PriceListConditionalBand $band,
        string $metricValue,
    ): bool {
        $minimum = $this->nullableDecimal(
            $band->getAttribute('minimum_value'),
            'Conditional band minimum',
            self::METRIC_SCALE,
        );

        $maximum = $this->nullableDecimal(
            $band->getAttribute('maximum_value'),
            'Conditional band maximum',
            self::METRIC_SCALE,
        );

        $minimumInclusive =
            (bool) $band->getAttribute(
                'minimum_inclusive',
            );

        $maximumInclusive =
            (bool) $band->getAttribute(
                'maximum_inclusive',
            );

        if ($minimum !== null) {
            $comparison = bccomp(
                $metricValue,
                $minimum,
                self::METRIC_SCALE,
            );

            if (
                $comparison < 0
                || (
                    $comparison === 0
                    && ! $minimumInclusive
                )
            ) {
                return false;
            }
        }

        if ($maximum !== null) {
            $comparison = bccomp(
                $metricValue,
                $maximum,
                self::METRIC_SCALE,
            );

            if (
                $comparison > 0
                || (
                    $comparison === 0
                    && ! $maximumInclusive
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $aggregate
     */
    private function amountPerUnit(
        array $aggregate,
        string $adjustmentValue,
        ?string &$rewardQuantityValue,
    ): string {
        if (
            $aggregate['reward_quantity_source'] === null
            || $aggregate['reward_quantity_value'] === null
        ) {
            throw new LogicException(
                'Amount-per-unit reward requires an explicit reward quantity.',
            );
        }

        $rewardQuantityValue = $this->decimal(
            $aggregate['reward_quantity_value'],
            'Reward quantity',
            self::CALCULATION_SCALE,
        );

        $rewardQuantityValue =
            $this->normalizeOutputDecimal(
                $rewardQuantityValue,
            );

        return bcmul(
            $aggregate['reward_quantity_value'],
            $adjustmentValue,
            self::CALCULATION_SCALE,
        );
    }

    /**
     * @param  array<string, int|float|string>  $baseItemAmounts
     */
    private function percentageOfItem(
        PriceListConditionalRule $rule,
        array $baseItemAmounts,
        string $percentage,
        ?string &$targetItemCode,
        ?string &$targetItemAmount,
    ): string {
        $targetItemCode = $this->requiredString(
            $rule,
            'reward_target_item_code',
        );

        if (! array_key_exists($targetItemCode, $baseItemAmounts)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Base amount for pricing item [%s] is missing.',
                    $targetItemCode,
                ),
            );
        }

        $targetItemAmount = $this->decimal(
            $baseItemAmounts[$targetItemCode],
            sprintf(
                'Base amount for pricing item [%s]',
                $targetItemCode,
            ),
            self::CALCULATION_SCALE,
        );

        $targetItemAmount =
            $this->normalizeOutputDecimal(
                $targetItemAmount,
            );

        $product = bcmul(
            $targetItemAmount,
            $percentage,
            self::CALCULATION_SCALE,
        );

        return bcdiv(
            $product,
            '100',
            self::CALCULATION_SCALE,
        );
    }

    /**
     * @param  array<string, mixed>  $aggregate
     */
    private function assertAggregateMatchesRule(
        PriceListConditionalRule $rule,
        array $aggregate,
    ): void {
        $contracts = [
            'evaluation_scope' => 'evaluation_scope',
            'metric_numerator_source' => 'metric_numerator_source',
            'metric_denominator_source' => 'metric_denominator_source',
            'reward_quantity_source' => 'reward_quantity_source',
        ];

        foreach ($contracts as $aggregateKey => $ruleAttribute) {
            if (! array_key_exists($aggregateKey, $aggregate)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Conditional aggregate field [%s] is missing.',
                        $aggregateKey,
                    ),
                );
            }

            if (
                $aggregate[$aggregateKey]
                !== $rule->getAttribute($ruleAttribute)
            ) {
                throw new LogicException(
                    sprintf(
                        'Conditional aggregate field [%s] does not match the rule.',
                        $aggregateKey,
                    ),
                );
            }
        }

        foreach ([
            'metric_numerator_value',
            'metric_denominator_value',
            'reward_quantity_value',
        ] as $requiredKey) {
            if (! array_key_exists($requiredKey, $aggregate)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Conditional aggregate field [%s] is missing.',
                        $requiredKey,
                    ),
                );
            }
        }
    }

    private function roundingScale(
        PriceListConditionalRule $rule,
    ): int {
        $value = $rule->getAttribute(
            'rounding_scale',
        );

        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && ctype_digit($value)
            )
        ) {
            throw new LogicException(
                'Conditional rounding scale must be an integer.',
            );
        }

        $scale = (int) $value;

        if ($scale < 0 || $scale > 6) {
            throw new LogicException(
                'Conditional rounding scale must be between 0 and 6.',
            );
        }

        return $scale;
    }

    private function requiredString(
        PriceListConditionalRule $rule,
        string $attribute,
    ): string {
        $value = $rule->getAttribute($attribute);

        if (
            ! is_string($value)
            || trim($value) === ''
        ) {
            throw new LogicException(
                sprintf(
                    'Conditional rule attribute [%s] is required.',
                    $attribute,
                ),
            );
        }

        return $value;
    }

    private function nullableString(
        PriceListConditionalRule $rule,
        string $attribute,
    ): ?string {
        $value = $rule->getAttribute($attribute);

        if ($value === null) {
            return null;
        }

        return $this->requiredString(
            $rule,
            $attribute,
        );
    }

    private function positiveIntegerAttribute(
        PriceListConditionalBand $band,
        string $attribute,
    ): int {
        $value = $band->getAttribute($attribute);

        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && ctype_digit($value)
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Conditional band attribute [%s] must be a positive integer.',
                    $attribute,
                ),
            );
        }

        $integer = (int) $value;

        if ($integer < 1) {
            throw new LogicException(
                sprintf(
                    'Conditional band attribute [%s] must be positive.',
                    $attribute,
                ),
            );
        }

        return $integer;
    }

    private function nullablePositiveId(
        PriceListConditionalBand $band,
    ): ?int {
        $value = $band->getKey();

        if ($value === null) {
            return null;
        }

        $id = (int) $value;

        return $id > 0
            ? $id
            : null;
    }

    private function decimal(
        mixed $value,
        string $label,
        int $scale,
    ): string {
        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (
            ! is_string($value)
            || preg_match(
                '/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative decimal.',
                    $label,
                ),
            );
        }

        return bcadd(
            $value,
            '0',
            $scale,
        );
    }

    private function nullableDecimal(
        mixed $value,
        string $label,
        int $scale,
    ): ?string {
        if ($value === null) {
            return null;
        }

        return $this->decimal(
            $value,
            $label,
            $scale,
        );
    }

    private function roundHalfUp(
        string $value,
        int $scale,
    ): string {
        $increment =
            $scale === 0
                ? '0.5'
                : '0.'
                    .str_repeat('0', $scale)
                    .'5';

        $adjusted = bcadd(
            $value,
            $increment,
            $scale + 1,
        );

        return bcdiv(
            $adjusted,
            '1',
            $scale,
        );
    }

    private function zero(
        int $scale,
    ): string {
        return bcadd(
            '0',
            '0',
            $scale,
        );
    }

    private function normalizeOutputDecimal(
        string $value,
    ): string {
        if (! str_contains($value, '.')) {
            return $value;
        }

        $normalized = rtrim(
            rtrim(
                $value,
                '0',
            ),
            '.',
        );

        return $normalized === ''
            ? '0'
            : $normalized;
    }
}
