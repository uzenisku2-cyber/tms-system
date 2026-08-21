<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;

final class DriverQualityEvaluator
{
    /**
     * @param  array<string, int|null>  $metrics
     * @return array{
     *     applied:bool,
     *     complete:bool,
     *     reason:string,
     *     method:string|null,
     *     numerator_sources:list<string>,
     *     numerator_parcels:int|null,
     *     denominator_parcels:int|null,
     *     value_percent:float|null
     * }
     */
    public function evaluate(
        ?DriverQualityProfileVersion $version,
        array $metrics,
    ): array {
        if (! $version instanceof DriverQualityProfileVersion) {
            return $this->result(reason: 'no_profile');
        }

        if ($version->isDisabled()) {
            return $this->result(
                reason: 'disabled',
                method: DriverQualityProfileVersion::METHOD_DISABLED,
                complete: true,
            );
        }

        if (
            $version->calculation_method
            !== DriverQualityProfileVersion::METHOD_PROCESSED_SHARE
        ) {
            return $this->result(
                reason: 'unsupported_method',
                method: (string) $version->calculation_method,
            );
        }

        $components = $version->relationLoaded('components')
            ? $version->components
            : $version->components()->get();

        /** @var list<string> $sources */
        $sources = $components
            ->pluck('source_code')
            ->filter(
                static fn (mixed $source): bool => is_string($source),
            )
            ->values()
            ->all();

        if ($sources === []) {
            return $this->result(
                reason: 'invalid_profile',
                method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
            );
        }

        foreach ($sources as $source) {
            if (! in_array($source, DriverQualityProfileComponent::SOURCES, true)) {
                return $this->result(
                    reason: 'invalid_profile',
                    method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                    sources: $sources,
                );
            }
        }

        $loaded = $metrics['loaded_parcels'] ?? null;

        if (! is_int($loaded) || $loaded < 0) {
            return $this->result(
                reason: 'incomplete_data',
                method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                sources: $sources,
            );
        }

        if ($loaded === 0) {
            return $this->result(
                reason: 'zero_loaded',
                method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                complete: true,
                sources: $sources,
                denominator: 0,
            );
        }

        $numerator = 0;

        foreach ($sources as $source) {
            $value = $metrics[$source] ?? null;

            if (! is_int($value) || $value < 0) {
                return $this->result(
                    reason: 'incomplete_data',
                    method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                    sources: $sources,
                    denominator: $loaded,
                );
            }

            $numerator += $value;
        }

        if ($numerator > $loaded) {
            return $this->result(
                reason: 'inconsistent_data',
                method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                sources: $sources,
                numerator: $numerator,
                denominator: $loaded,
            );
        }

        return $this->result(
            reason: 'evaluated',
            method: DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
            applied: true,
            complete: true,
            sources: $sources,
            numerator: $numerator,
            denominator: $loaded,
            value: round($numerator / $loaded * 100, 2),
        );
    }

    /**
     * @param  list<string>  $sources
     * @return array{
     *     applied:bool,
     *     complete:bool,
     *     reason:string,
     *     method:string|null,
     *     numerator_sources:list<string>,
     *     numerator_parcels:int|null,
     *     denominator_parcels:int|null,
     *     value_percent:float|null
     * }
     */
    private function result(
        string $reason,
        ?string $method = null,
        bool $applied = false,
        bool $complete = false,
        array $sources = [],
        ?int $numerator = null,
        ?int $denominator = null,
        ?float $value = null,
    ): array {
        return [
            'applied' => $applied,
            'complete' => $complete,
            'reason' => $reason,
            'method' => $method,
            'numerator_sources' => $sources,
            'numerator_parcels' => $numerator,
            'denominator_parcels' => $denominator,
            'value_percent' => $value,
        ];
    }
}
