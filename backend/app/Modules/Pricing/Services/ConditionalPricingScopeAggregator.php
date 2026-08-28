<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

final class ConditionalPricingScopeAggregator
{
    private const SCALE = 6;

    /**
     * @param  list<array<string, mixed>>  $snapshots
     * @return array{
     *     evaluation_scope: string,
     *     driver_id: int,
     *     period: string,
     *     route_count: int,
     *     metric_numerator_sources: list<string>,
     *     metric_numerator_source: string,
     *     metric_numerator_value: string,
     *     metric_denominator_sources: list<string>,
     *     metric_denominator_source: string|null,
     *     metric_denominator_value: string|null,
     *     reward_quantity_sources: list<string>,
     *     reward_quantity_source: string|null,
     *     reward_quantity_value: string|null,
     *     reward_quantity_values: array<string, string>
     * }
     */
    public function aggregate(
        PriceListConditionalRule $rule,
        array $snapshots,
    ): array {
        if ($snapshots === []) {
            throw new InvalidArgumentException(
                'At least one financial snapshot is required.',
            );
        }

        $scope = $this->requiredRuleString(
            $rule,
            'evaluation_scope',
        );

        if (
            ! in_array(
                $scope,
                PriceListConditionalRule::EVALUATION_SCOPES,
                true,
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Unsupported conditional evaluation scope [%s].',
                    $scope,
                ),
            );
        }

        if (
            $scope ===
                PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE
            && count($snapshots) !== 1
        ) {
            throw new InvalidArgumentException(
                'Per-route evaluation requires exactly one snapshot.',
            );
        }

        $numeratorSources = $this->componentSources(
            $rule,
            'numerator',
            true,
        );
        $denominatorSources = $this->componentSources(
            $rule,
            'denominator',
            false,
        );
        $rewardQuantitySources = $this->rewardSources($rule);
        $numeratorSource = $numeratorSources[0];
        $denominatorSource = $denominatorSources[0] ?? null;
        $rewardQuantitySource = $rewardQuantitySources[0] ?? null;

        $first = $snapshots[0];

        $driverId = $this->driverId($first);
        $serviceDate = $this->serviceDate($first);

        $period =
            in_array(
                $scope,
                [
                    PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
                    PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
                ],
                true,
            )
                ? substr($serviceDate, 0, 7)
                : $serviceDate;

        $numerator = '0.000000';
        $denominator =
            $denominatorSource !== null
                ? '0.000000'
                : null;

        $rewardQuantity =
            $rewardQuantitySource !== null
                ? '0.000000'
                : null;
        $rewardQuantityValues = array_fill_keys(
            $rewardQuantitySources,
            '0.000000',
        );

        foreach ($snapshots as $snapshot) {
            $snapshotDriverId =
                $this->driverId($snapshot);

            $snapshotServiceDate =
                $this->serviceDate($snapshot);

            if (
                in_array(
                    $scope,
                    [
                        PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
                        PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
                    ],
                    true,
                )
            ) {
                if (
                    $scope === PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
                    && $snapshotDriverId !== $driverId
                ) {
                    throw new InvalidArgumentException(
                        'Monthly-driver scope cannot mix drivers.',
                    );
                }

                if (
                    substr($snapshotServiceDate, 0, 7)
                    !== $period
                ) {
                    throw new InvalidArgumentException(
                        'Monthly scope cannot mix calendar months.',
                    );
                }
            }

            foreach ($numeratorSources as $source) {
                $numerator = bcadd(
                    $numerator,
                    $this->sourceValue($snapshot, $source),
                    self::SCALE,
                );
            }

            foreach ($denominatorSources as $source) {
                $denominator = bcadd(
                    (string) $denominator,
                    $this->sourceValue($snapshot, $source),
                    self::SCALE,
                );
            }

            foreach ($rewardQuantitySources as $source) {
                $sourceValue = $this->sourceValue($snapshot, $source);
                $rewardQuantity = bcadd(
                    (string) $rewardQuantity,
                    $sourceValue,
                    self::SCALE,
                );
                $rewardQuantityValues[$source] = bcadd(
                    $rewardQuantityValues[$source],
                    $sourceValue,
                    self::SCALE,
                );
            }
        }

        return [
            'evaluation_scope' => $scope,
            'driver_id' => $driverId,
            'period' => $period,
            'route_count' => count($snapshots),
            'metric_numerator_sources' => $numeratorSources,
            'metric_numerator_source' => $numeratorSource,
            'metric_numerator_value' => $numerator,
            'metric_denominator_sources' => $denominatorSources,
            'metric_denominator_source' => $denominatorSource,
            'metric_denominator_value' => $denominator,
            'reward_quantity_sources' => $rewardQuantitySources,
            'reward_quantity_source' => $rewardQuantitySource,
            'reward_quantity_value' => $rewardQuantity,
            'reward_quantity_values' => $rewardQuantityValues,
        ];
    }

    /** @return list<string> */
    private function componentSources(
        PriceListConditionalRule $rule,
        string $role,
        bool $required,
    ): array {
        $components = $rule->relationLoaded('metricComponents')
            ? $rule->getRelation('metricComponents')->filter(
                static fn (mixed $component): bool =>
                    $component->getAttribute('component_role') === $role,
            )
            : (! $rule->exists
                ? new Collection
                : ($role === 'numerator'
                    ? $rule->numeratorComponents()->get()
                    : $rule->denominatorComponents()->get()));
        $sources = $components
            ->pluck('metric_source')
            ->filter(static fn (mixed $source): bool => is_string($source))
            ->values()
            ->all();

        if ($sources === []) {
            $legacy = $rule->getAttribute(
                $role === 'numerator'
                    ? 'metric_numerator_source'
                    : 'metric_denominator_source',
            );

            if (is_string($legacy)) {
                $sources[] = $legacy;
            }
        }

        if ($required && $sources === []) {
            throw new LogicException(
                'Conditional numerator requires at least one source.',
            );
        }

        foreach ($sources as $source) {
            $this->assertSource($source);
        }

        return $sources;
    }

    /** @return list<string> */
    private function rewardSources(PriceListConditionalRule $rule): array
    {
        $components = $rule->relationLoaded('rewardComponents')
            ? $rule->getRelation('rewardComponents')
            : ($rule->exists
                ? $rule->rewardComponents()->orderBy('position')->get()
                : new Collection);
        $sources = $components->pluck('metric_source')
            ->filter(static fn (mixed $source): bool => is_string($source))
            ->values()
            ->all();

        if ($sources === []) {
            $legacy = $rule->getAttribute('reward_quantity_source');

            if (is_string($legacy)) {
                $sources[] = $legacy;
            }
        }

        foreach ($sources as $source) {
            $this->assertSource($source);
        }

        return $sources;
    }

    private function assertSource(string $source): void
    {
        if (! in_array($source, PriceListConditionalRule::METRIC_SOURCES, true)) {
            throw new LogicException(
                sprintf('Unsupported conditional metric source [%s].', $source),
            );
        }
    }

    private function requiredSource(
        PriceListConditionalRule $rule,
        string $attribute,
    ): string {
        $source = $this->requiredRuleString(
            $rule,
            $attribute,
        );

        if (
            ! in_array(
                $source,
                PriceListConditionalRule::METRIC_SOURCES,
                true,
            )
        ) {
            throw new LogicException(
                sprintf(
                    'Unsupported conditional metric source [%s].',
                    $source,
                ),
            );
        }

        return $source;
    }

    private function nullableSource(
        PriceListConditionalRule $rule,
        string $attribute,
    ): ?string {
        $value = $rule->getAttribute($attribute);

        if ($value === null) {
            return null;
        }

        return $this->requiredSource(
            $rule,
            $attribute,
        );
    }

    private function requiredRuleString(
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

    /** @param array<string, mixed> $snapshot */
    private function driverId(array $snapshot): int
    {
        $value =
            $snapshot['performed_by_driver_id']
            ?? null;

        if (
            ! is_int($value)
            || $value < 1
        ) {
            throw new InvalidArgumentException(
                'Financial snapshot requires a valid performing driver.',
            );
        }

        return $value;
    }

    /** @param array<string, mixed> $snapshot */
    private function serviceDate(array $snapshot): string
    {
        $value =
            $snapshot['service_date']
            ?? null;

        if (
            ! is_string($value)
            || preg_match(
                '/^\d{4}-\d{2}-\d{2}$/D',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'Financial snapshot requires a valid service date.',
            );
        }

        [$year, $month, $day] =
            array_map(
                'intval',
                explode('-', $value),
            );

        if (! checkdate($month, $day, $year)) {
            throw new InvalidArgumentException(
                'Financial snapshot contains an invalid service date.',
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function sourceValue(
        array $snapshot,
        string $source,
    ): string {
        if (! array_key_exists($source, $snapshot)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Financial snapshot metric [%s] is missing.',
                    $source,
                ),
            );
        }

        $value = $snapshot[$source];

        if (is_int($value)) {
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
                    'Financial snapshot metric [%s] must be non-negative numeric.',
                    $source,
                ),
            );
        }

        return bcadd(
            $value,
            '0',
            self::SCALE,
        );
    }
}
