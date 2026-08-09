<?php

namespace App\Modules\DailyReports\Services;

use InvalidArgumentException;

final class DailyReportCalculations
{
    public const KILOMETRE_ATTENTION_THRESHOLD_PERCENT = 10;

    private const HUNDREDTHS_PER_KILOMETRE = 100;

    private const MAX_KILOMETRES = 99_999_999.99;

    public function totalProcessedParcels(
        int $deliveredParcels,
        int $redirectedParcels,
        int $undeliveredParcels,
    ): int {
        $this->assertNonNegativeParcelCount(
            $deliveredParcels,
            'Delivered parcels',
        );

        $this->assertNonNegativeParcelCount(
            $redirectedParcels,
            'Redirected parcels',
        );

        $this->assertNonNegativeParcelCount(
            $undeliveredParcels,
            'Undelivered parcels',
        );

        return $deliveredParcels
            + $redirectedParcels
            + $undeliveredParcels;
    }

    public function differenceKm(
        float $plannedKm,
        float $actualKm,
    ): float {
        $plannedHundredths = $this->kilometresToHundredths(
            $plannedKm,
            'Planned kilometres',
        );

        $actualHundredths = $this->kilometresToHundredths(
            $actualKm,
            'Actual kilometres',
        );

        return (
            $actualHundredths - $plannedHundredths
        ) / self::HUNDREDTHS_PER_KILOMETRE;
    }

    public function deviationPercentage(
        float $plannedKm,
        float $actualKm,
    ): ?float {
        $plannedHundredths = $this->kilometresToHundredths(
            $plannedKm,
            'Planned kilometres',
        );

        $actualHundredths = $this->kilometresToHundredths(
            $actualKm,
            'Actual kilometres',
        );

        if ($plannedHundredths === 0) {
            return null;
        }

        $differenceHundredths = abs(
            $actualHundredths - $plannedHundredths,
        );

        return round(
            (
                $differenceHundredths
                / $plannedHundredths
            ) * 100,
            2,
            PHP_ROUND_HALF_UP,
        );
    }

    public function requiresKilometreAttention(
        float $plannedKm,
        float $actualKm,
    ): bool {
        $plannedHundredths = $this->kilometresToHundredths(
            $plannedKm,
            'Planned kilometres',
        );

        $actualHundredths = $this->kilometresToHundredths(
            $actualKm,
            'Actual kilometres',
        );

        if ($plannedHundredths === 0) {
            return true;
        }

        $differenceHundredths = abs(
            $actualHundredths - $plannedHundredths,
        );

        return (
            $differenceHundredths * 100
        ) > (
            $plannedHundredths
            * self::KILOMETRE_ATTENTION_THRESHOLD_PERCENT
        );
    }

    private function assertNonNegativeParcelCount(
        int $value,
        string $field,
    ): void {
        if ($value < 0) {
            throw new InvalidArgumentException(
                "$field must be a non-negative integer.",
            );
        }
    }

    private function kilometresToHundredths(
        float $value,
        string $field,
    ): int {
        if (
            ! is_finite($value)
            || $value < 0
            || $value > self::MAX_KILOMETRES
        ) {
            throw new InvalidArgumentException(
                "$field must be a finite non-negative value within the supported database range.",
            );
        }

        return (int) round(
            $value * self::HUNDREDTHS_PER_KILOMETRE,
            0,
            PHP_ROUND_HALF_UP,
        );
    }

    public function notDeliveredParcels(
        int $loadedParcels,
        int $deliveredParcels,
        int $redirectedParcels,
        int $customerRejectedParcels,
    ): int {
        foreach ([
            $loadedParcels,
            $deliveredParcels,
            $redirectedParcels,
            $customerRejectedParcels,
        ] as $value) {
            if ($value < 0) {
                throw new InvalidArgumentException(
                    'Parcel counts cannot be negative.',
                );
            }
        }

        return $loadedParcels
            - $deliveredParcels
            - $redirectedParcels
            - $customerRejectedParcels;
    }
}
