<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\PriceListConditionalRule;
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
     *     metric_numerator_source: string,
     *     metric_numerator_value: string,
     *     metric_denominator_source: string|null,
     *     metric_denominator_value: string|null,
     *     reward_quantity_source: string|null,
     *     reward_quantity_value: string|null
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

        $numeratorSource = $this->requiredSource(
            $rule,
            'metric_numerator_source',
        );

        $denominatorSource = $this->nullableSource(
            $rule,
            'metric_denominator_source',
        );

        $rewardQuantitySource = $this->nullableSource(
            $rule,
            'reward_quantity_source',
        );

        $first = $snapshots[0];

        $driverId = $this->driverId($first);
        $serviceDate = $this->serviceDate($first);

        $period =
            $scope ===
                PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
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

        foreach ($snapshots as $snapshot) {
            $snapshotDriverId =
                $this->driverId($snapshot);

            $snapshotServiceDate =
                $this->serviceDate($snapshot);

            if (
                $scope ===
                    PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER
            ) {
                if ($snapshotDriverId !== $driverId) {
                    throw new InvalidArgumentException(
                        'Monthly-driver scope cannot mix drivers.',
                    );
                }

                if (
                    substr($snapshotServiceDate, 0, 7)
                    !== $period
                ) {
                    throw new InvalidArgumentException(
                        'Monthly-driver scope cannot mix calendar months.',
                    );
                }
            }

            $numerator = bcadd(
                $numerator,
                $this->sourceValue(
                    $snapshot,
                    $numeratorSource,
                ),
                self::SCALE,
            );

            if ($denominatorSource !== null) {
                $denominator = bcadd(
                    (string) $denominator,
                    $this->sourceValue(
                        $snapshot,
                        $denominatorSource,
                    ),
                    self::SCALE,
                );
            }

            if ($rewardQuantitySource !== null) {
                $rewardQuantity = bcadd(
                    (string) $rewardQuantity,
                    $this->sourceValue(
                        $snapshot,
                        $rewardQuantitySource,
                    ),
                    self::SCALE,
                );
            }
        }

        return [
            'evaluation_scope' => $scope,
            'driver_id' => $driverId,
            'period' => $period,
            'route_count' => count($snapshots),
            'metric_numerator_source' => $numeratorSource,
            'metric_numerator_value' => $numerator,
            'metric_denominator_source' => $denominatorSource,
            'metric_denominator_value' => $denominator,
            'reward_quantity_source' => $rewardQuantitySource,
            'reward_quantity_value' => $rewardQuantity,
        ];
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
