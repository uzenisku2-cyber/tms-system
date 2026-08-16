<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListItem;
use Illuminate\Validation\ValidationException;
use LogicException;

final class ConditionalPriceListRulePayload
{
    /**
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    public function fromInput(array $input): array
    {
        $value = $input['conditional_rules'] ?? [];

        if (! is_array($value)) {
            throw new LogicException(
                'Validated conditional pricing rules are unavailable.',
            );
        }

        $rules = [];
        $codes = [];

        foreach (array_values($value) as $ruleIndex => $rule) {
            $path = 'conditional_rules.'.$ruleIndex;

            if (! is_array($rule)) {
                throw new LogicException(
                    'A validated conditional pricing rule is invalid.',
                );
            }

            $code = $this->requiredString($rule, 'code');

            if (
                preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code) !== 1
                || array_key_exists($code, $codes)
            ) {
                $this->validationFailure(
                    $path.'.code',
                    'Conditional pricing rule codes must be unique and canonical.',
                );
            }

            $codes[$code] = true;

            $metricType = $this->allowedString(
                $rule,
                'metric_type',
                PriceListConditionalRule::METRIC_TYPES,
            );

            $numeratorSources = $this->sourceList(
                $rule,
                'metric_numerator_sources',
                $path,
                true,
            );

            $denominatorSources = $this->sourceList(
                $rule,
                'metric_denominator_sources',
                $path,
                false,
            );

            if (
                $metricType ===
                    PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE
                && $denominatorSources === []
            ) {
                $this->validationFailure(
                    $path.'.metric_denominator_sources',
                    'A percentage metric requires at least one denominator source.',
                );
            }

            if (
                $metricType ===
                    PriceListConditionalRule::METRIC_TYPE_QUANTITY
                && $denominatorSources !== []
            ) {
                $this->validationFailure(
                    $path.'.metric_denominator_sources',
                    'A quantity metric cannot contain denominator sources.',
                );
            }

            $rewardMethod = $this->allowedString(
                $rule,
                'reward_method',
                PriceListConditionalRule::REWARD_METHODS,
            );

            $rewardQuantitySource = $this->nullableAllowedString(
                $rule,
                'reward_quantity_source',
                PriceListConditionalRule::METRIC_SOURCES,
            );

            $rewardTargetItemCode = $this->nullableAllowedString(
                $rule,
                'reward_target_item_code',
                PriceListItem::CODES,
            );

            $this->assertRewardShape(
                $path,
                $rewardMethod,
                $rewardQuantitySource,
                $rewardTargetItemCode,
            );

            $roundingScale = $this->integerValue(
                $rule['rounding_scale'] ?? 2,
            );

            if ($roundingScale < 0 || $roundingScale > 6) {
                $this->validationFailure(
                    $path.'.rounding_scale',
                    'The rounding scale must be between zero and six.',
                );
            }

            $rules[] = [
                'code' => $code,
                'name' => $this->requiredString($rule, 'name'),
                'description' => $this->nullableString(
                    $rule,
                    'description',
                ),
                'metric_type' => $metricType,
                'metric_numerator_sources' => $numeratorSources,
                'metric_denominator_sources' => $denominatorSources,
                'evaluation_scope' => $this->allowedString(
                    $rule,
                    'evaluation_scope',
                    PriceListConditionalRule::EVALUATION_SCOPES,
                ),
                'reward_method' => $rewardMethod,
                'reward_quantity_source' => $rewardQuantitySource,
                'reward_target_item_code' => $rewardTargetItemCode,
                'rounding_scale' => $roundingScale,
                'bands' => $this->bands($rule, $path),
            ];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<string>
     */
    private function sourceList(
        array $rule,
        string $key,
        string $path,
        bool $required,
    ): array {
        $value = $rule[$key] ?? null;

        if (! is_array($value)) {
            throw new LogicException(
                sprintf('Validated field [%s] must be an array.', $key),
            );
        }

        $sources = [];

        foreach (array_values($value) as $source) {
            if (
                ! is_string($source)
                || ! in_array(
                    $source,
                    PriceListConditionalRule::METRIC_SOURCES,
                    true,
                )
            ) {
                throw new LogicException(
                    sprintf('Validated source list [%s] is invalid.', $key),
                );
            }

            if (in_array($source, $sources, true)) {
                $this->validationFailure(
                    $path.'.'.$key,
                    'Metric sources cannot be duplicated in the same formula role.',
                );
            }

            $sources[] = $source;
        }

        if ($required && $sources === []) {
            $this->validationFailure(
                $path.'.'.$key,
                'At least one metric source is required.',
            );
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<array<string, mixed>>
     */
    private function bands(array $rule, string $path): array
    {
        $value = $rule['bands'] ?? null;

        if (! is_array($value) || $value === []) {
            $this->validationFailure(
                $path.'.bands',
                'At least one conditional pricing band is required.',
            );
        }

        $bands = [];

        foreach (array_values($value) as $bandIndex => $band) {
            $bandPath = $path.'.bands.'.$bandIndex;

            if (! is_array($band)) {
                throw new LogicException(
                    'A validated conditional pricing band is invalid.',
                );
            }

            $minimumValue = $this->nullableNumber(
                $band['minimum_value'] ?? null,
            );

            $maximumValue = $this->nullableNumber(
                $band['maximum_value'] ?? null,
            );

            $minimumInclusive = $this->booleanValue(
                $band['minimum_inclusive'] ?? true,
            );

            $maximumInclusive = $this->booleanValue(
                $band['maximum_inclusive'] ?? false,
            );

            $adjustmentValue = $this->requiredNumber(
                $band['adjustment_value'] ?? null,
            );

            if ($minimumValue === null && $maximumValue === null) {
                $this->validationFailure(
                    $bandPath,
                    'A conditional pricing band requires at least one boundary.',
                );
            }

            if (
                ($minimumValue !== null && (float) $minimumValue < 0)
                || ($maximumValue !== null && (float) $maximumValue < 0)
                || (float) $adjustmentValue < 0
            ) {
                $this->validationFailure(
                    $bandPath,
                    'Conditional pricing boundaries and adjustment values must be non-negative.',
                );
            }

            if ($minimumValue !== null && $maximumValue !== null) {
                $comparison = (float) $minimumValue <=>
                    (float) $maximumValue;

                if (
                    $comparison > 0
                    || (
                        $comparison === 0
                        && ! ($minimumInclusive && $maximumInclusive)
                    )
                ) {
                    $this->validationFailure(
                        $bandPath,
                        'The conditional pricing band boundaries are invalid.',
                    );
                }
            }

            $bands[] = [
                'minimum_value' => $minimumValue,
                'maximum_value' => $maximumValue,
                'minimum_inclusive' => $minimumInclusive,
                'maximum_inclusive' => $maximumInclusive,
                'adjustment_value' => $adjustmentValue,
            ];
        }

        foreach ($bands as $leftIndex => $left) {
            foreach ($bands as $rightIndex => $right) {
                if (
                    $rightIndex <= $leftIndex
                    || ! $this->bandsOverlap($left, $right)
                ) {
                    continue;
                }

                $this->validationFailure(
                    $path.'.bands',
                    'Conditional pricing bands cannot overlap.',
                );
            }
        }

        return $bands;
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function bandsOverlap(array $left, array $right): bool
    {
        $leftMaximum = $left['maximum_value'];
        $rightMinimum = $right['minimum_value'];

        if ($leftMaximum !== null && $rightMinimum !== null) {
            $comparison = (float) $leftMaximum <=>
                (float) $rightMinimum;

            if ($comparison < 0) {
                return false;
            }

            if (
                $comparison === 0
                && ! (
                    $left['maximum_inclusive']
                    && $right['minimum_inclusive']
                )
            ) {
                return false;
            }
        }

        $rightMaximum = $right['maximum_value'];
        $leftMinimum = $left['minimum_value'];

        if ($rightMaximum !== null && $leftMinimum !== null) {
            $comparison = (float) $rightMaximum <=>
                (float) $leftMinimum;

            if ($comparison < 0) {
                return false;
            }

            if (
                $comparison === 0
                && ! (
                    $right['maximum_inclusive']
                    && $left['minimum_inclusive']
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function assertRewardShape(
        string $path,
        string $rewardMethod,
        ?string $rewardQuantitySource,
        ?string $rewardTargetItemCode,
    ): void {
        $valid = match ($rewardMethod) {
            PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT => $rewardQuantitySource !== null
                && $rewardTargetItemCode === null,
            PriceListConditionalRule::REWARD_METHOD_FIXED_AMOUNT => $rewardQuantitySource === null
                && $rewardTargetItemCode === null,
            PriceListConditionalRule::REWARD_METHOD_PERCENTAGE_OF_ITEM => $rewardQuantitySource === null
                && $rewardTargetItemCode !== null,
            default => false,
        };

        if (! $valid) {
            $this->validationFailure(
                $path.'.reward_method',
                'The selected reward method has incompatible quantity or target fields.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $allowed
     */
    private function allowedString(
        array $input,
        string $key,
        array $allowed,
    ): string {
        $value = $this->requiredString($input, $key);

        if (! in_array($value, $allowed, true)) {
            throw new LogicException(
                sprintf('Validated field [%s] is not allowed.', $key),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $allowed
     */
    private function nullableAllowedString(
        array $input,
        string $key,
        array $allowed,
    ): ?string {
        $value = $this->nullableString($input, $key);

        if ($value !== null && ! in_array($value, $allowed, true)) {
            throw new LogicException(
                sprintf('Validated field [%s] is not allowed.', $key),
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $input */
    private function requiredString(array $input, string $key): string
    {
        $value = $input[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new LogicException(
                sprintf('Validated field [%s] must be a non-empty string.', $key),
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $input */
    private function nullableString(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                sprintf('Validated field [%s] must be a string or null.', $key),
            );
        }

        return $value;
    }

    private function nullableNumber(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->requiredNumber($value);
    }

    private function requiredNumber(mixed $value): string
    {
        if (
            (! is_string($value) && ! is_int($value) && ! is_float($value))
            || ! is_numeric($value)
        ) {
            throw new LogicException(
                'A validated conditional pricing number is invalid.',
            );
        }

        return (string) $value;
    }

    private function integerValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new LogicException(
            'A validated conditional pricing integer is invalid.',
        );
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        throw new LogicException(
            'A validated conditional pricing boolean is invalid.',
        );
    }

    private function validationFailure(
        string $field,
        string $message,
    ): never {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
