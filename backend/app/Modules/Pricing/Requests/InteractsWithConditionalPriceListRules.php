<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListItem;
use Closure;
use Illuminate\Validation\Rule;

trait InteractsWithConditionalPriceListRules
{
    /**
     * @param  array<mixed>  $rules
     * @return array<mixed>
     */
    protected function normalizeConditionalPriceListRules(
        array $rules,
    ): array {
        $normalizedRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                $normalizedRules[] = $rule;

                continue;
            }

            $normalizedRule = $rule;

            foreach ([
                'code',
                'name',
                'metric_type',
                'evaluation_scope',
                'reward_method',
            ] as $field) {
                $value = $rule[$field] ?? null;

                if (is_string($value)) {
                    $normalizedRule[$field] = trim($value);
                }
            }

            foreach ([
                'description',
                'reward_quantity_source',
                'reward_target_item_code',
            ] as $field) {
                $value = $rule[$field] ?? null;

                if (! is_string($value)) {
                    continue;
                }

                $value = trim($value);
                $normalizedRule[$field] = $value === ''
                    ? null
                    : $value;
            }

            foreach ([
                'metric_numerator_sources',
                'metric_denominator_sources',
            ] as $field) {
                $sources = $rule[$field] ?? null;

                if (! is_array($sources)) {
                    continue;
                }

                $normalizedRule[$field] = array_map(
                    static fn (mixed $source): mixed => is_string($source)
                            ? trim($source)
                            : $source,
                    $sources,
                );
            }

            $roundingScale = $rule['rounding_scale'] ?? null;

            if (is_string($roundingScale)) {
                $normalizedRule['rounding_scale'] = trim(
                    $roundingScale,
                );
            }

            $bands = $rule['bands'] ?? null;

            if (is_array($bands)) {
                $normalizedBands = [];

                foreach ($bands as $band) {
                    if (! is_array($band)) {
                        $normalizedBands[] = $band;

                        continue;
                    }

                    $normalizedBand = $band;

                    foreach ([
                        'minimum_value',
                        'maximum_value',
                        'adjustment_value',
                    ] as $field) {
                        $value = $band[$field] ?? null;

                        if (! is_string($value)) {
                            continue;
                        }

                        $value = trim($value);
                        $normalizedBand[$field] = (
                            $field !== 'adjustment_value'
                            && $value === ''
                        )
                            ? null
                            : $value;
                    }

                    $normalizedBands[] = $normalizedBand;
                }

                $normalizedRule['bands'] = $normalizedBands;
            }

            $normalizedRules[] = $normalizedRule;
        }

        return $normalizedRules;
    }

    private function distinctConditionalMetricSources(): Closure
    {
        return static function (
            string $attribute,
            mixed $value,
            Closure $fail,
        ): void {
            if (! is_array($value)) {
                return;
            }

            $sources = [];

            foreach ($value as $source) {
                if (! is_string($source)) {
                    continue;
                }

                if (in_array($source, $sources, true)) {
                    $fail(
                        "The {$attribute} field has a duplicate value.",
                    );

                    return;
                }

                $sources[] = $source;
            }
        };
    }

    /** @return array<string, list<mixed>> */
    protected function conditionalPriceListRuleRules(): array
    {
        return [
            'conditional_rules' => [
                'sometimes',
                'array',
            ],
            'conditional_rules.*' => [
                'required',
                'array:code,name,description,metric_type,metric_numerator_sources,metric_denominator_sources,evaluation_scope,reward_method,reward_quantity_source,reward_target_item_code,rounding_scale,bands',
            ],
            'conditional_rules.*.code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]{0,63}$/',
                'distinct:strict',
            ],
            'conditional_rules.*.name' => [
                'required',
                'string',
                'max:150',
            ],
            'conditional_rules.*.description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'conditional_rules.*.metric_type' => [
                'required',
                'string',
                Rule::in(PriceListConditionalRule::METRIC_TYPES),
            ],
            'conditional_rules.*.metric_numerator_sources' => [
                'required',
                'array',
                'min:1',
                $this->distinctConditionalMetricSources(),
            ],
            'conditional_rules.*.metric_numerator_sources.*' => [
                'required',
                'string',
                Rule::in(PriceListConditionalRule::METRIC_SOURCES),
            ],
            'conditional_rules.*.metric_denominator_sources' => [
                'present',
                'array',
                $this->distinctConditionalMetricSources(),
            ],
            'conditional_rules.*.metric_denominator_sources.*' => [
                'required',
                'string',
                Rule::in(PriceListConditionalRule::METRIC_SOURCES),
            ],
            'conditional_rules.*.evaluation_scope' => [
                'required',
                'string',
                Rule::in(PriceListConditionalRule::EVALUATION_SCOPES),
            ],
            'conditional_rules.*.reward_method' => [
                'required',
                'string',
                Rule::in(PriceListConditionalRule::REWARD_METHODS),
            ],
            'conditional_rules.*.reward_quantity_source' => [
                'nullable',
                'string',
                Rule::in(PriceListConditionalRule::METRIC_SOURCES),
            ],
            'conditional_rules.*.reward_target_item_code' => [
                'nullable',
                'string',
                Rule::in(PriceListItem::CODES),
            ],
            'conditional_rules.*.rounding_scale' => [
                'sometimes',
                'integer',
                'between:0,6',
            ],
            'conditional_rules.*.bands' => [
                'required',
                'array',
                'min:1',
            ],
            'conditional_rules.*.bands.*' => [
                'required',
                'array:minimum_value,maximum_value,minimum_inclusive,maximum_inclusive,adjustment_value',
            ],
            'conditional_rules.*.bands.*.minimum_value' => [
                'nullable',
                'numeric',
                'decimal:0,4',
                'between:0,9999999999.9999',
            ],
            'conditional_rules.*.bands.*.maximum_value' => [
                'nullable',
                'numeric',
                'decimal:0,4',
                'between:0,9999999999.9999',
            ],
            'conditional_rules.*.bands.*.minimum_inclusive' => [
                'sometimes',
                'boolean',
            ],
            'conditional_rules.*.bands.*.maximum_inclusive' => [
                'sometimes',
                'boolean',
            ],
            'conditional_rules.*.bands.*.adjustment_value' => [
                'required',
                'numeric',
                'decimal:0,4',
                'between:0,9999999999.9999',
            ],
        ];
    }
}
