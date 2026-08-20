<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

final class DriverPerformanceAccumulator
{
    private const HUNDREDTHS_PER_KILOMETRE = 100;

    private int $routeCount = 0;

    /** @var array<string, true> */
    private array $workDays = [];

    private ?string $firstServiceDate = null;

    private ?string $lastServiceDate = null;

    private int $parcelCompleteRouteCount = 0;

    private int $parcelIncompleteRouteCount = 0;

    private int $kilometreCompleteRouteCount = 0;

    private int $kilometreIncompleteRouteCount = 0;

    private int $loadedParcels = 0;

    private int $deliveredParcels = 0;

    private int $redirectedParcels = 0;

    private int $customerRejectedParcels = 0;

    private int $plannedKilometreHundredths = 0;

    private int $actualKilometreHundredths = 0;

    public function add(
        string $serviceDate,
        ?int $loadedParcels,
        ?int $deliveredParcels,
        ?int $redirectedParcels,
        ?int $customerRejectedParcels,
        mixed $plannedKilometres,
        mixed $actualKilometres,
    ): void {
        $this->routeCount++;
        $this->workDays[$serviceDate] = true;

        if (
            $this->firstServiceDate === null
            || $serviceDate < $this->firstServiceDate
        ) {
            $this->firstServiceDate = $serviceDate;
        }

        if (
            $this->lastServiceDate === null
            || $serviceDate > $this->lastServiceDate
        ) {
            $this->lastServiceDate = $serviceDate;
        }

        if (
            $loadedParcels === null
            || $deliveredParcels === null
            || $redirectedParcels === null
            || $customerRejectedParcels === null
        ) {
            $this->parcelIncompleteRouteCount++;
        } else {
            $this->parcelCompleteRouteCount++;
            $this->loadedParcels += $loadedParcels;
            $this->deliveredParcels += $deliveredParcels;
            $this->redirectedParcels += $redirectedParcels;
            $this->customerRejectedParcels +=
                $customerRejectedParcels;
        }

        if (
            $plannedKilometres === null
            || $actualKilometres === null
        ) {
            $this->kilometreIncompleteRouteCount++;
        } else {
            $this->kilometreCompleteRouteCount++;
            $this->plannedKilometreHundredths +=
                $this->kilometreHundredths(
                    $plannedKilometres,
                );
            $this->actualKilometreHundredths +=
                $this->kilometreHundredths(
                    $actualKilometres,
                );
        }
    }

    /**
     * @return array<string, int|float|string|null>
     */
    public function metrics(): array
    {
        $processedParcels =
            $this->deliveredParcels
            + $this->redirectedParcels
            + $this->customerRejectedParcels;

        $notDeliveredParcels =
            $this->loadedParcels
            - $processedParcels;

        $differenceKilometreHundredths =
            $this->actualKilometreHundredths
            - $this->plannedKilometreHundredths;

        return [
            'route_count' => $this->routeCount,
            'work_day_count' => count($this->workDays),
            'first_service_date' => $this->firstServiceDate,
            'last_service_date' => $this->lastServiceDate,
            'parcel_complete_route_count' => $this->parcelCompleteRouteCount,
            'parcel_incomplete_route_count' => $this->parcelIncompleteRouteCount,
            'kilometre_complete_route_count' => $this->kilometreCompleteRouteCount,
            'kilometre_incomplete_route_count' => $this->kilometreIncompleteRouteCount,
            'loaded_parcels' => $this->loadedParcels,
            'delivered_parcels' => $this->deliveredParcels,
            'redirected_parcels' => $this->redirectedParcels,
            'customer_rejected_parcels' => $this->customerRejectedParcels,
            'processed_parcels' => $processedParcels,
            'not_delivered_parcels' => $notDeliveredParcels,
            'processed_share_percent' => $this->percentage(
                $processedParcels,
                $this->loadedParcels,
            ),
            'delivered_share_percent' => $this->percentage(
                $this->deliveredParcels,
                $this->loadedParcels,
            ),
            'redirected_share_percent' => $this->percentage(
                $this->redirectedParcels,
                $this->loadedParcels,
            ),
            'customer_rejected_share_percent' => $this->percentage(
                $this->customerRejectedParcels,
                $this->loadedParcels,
            ),
            'not_delivered_share_percent' => $this->percentage(
                $notDeliveredParcels,
                $this->loadedParcels,
            ),
            'planned_km' => $this->formatKilometres(
                $this->plannedKilometreHundredths,
            ),
            'actual_km' => $this->formatKilometres(
                $this->actualKilometreHundredths,
            ),
            'difference_km' => $this->formatKilometres(
                $differenceKilometreHundredths,
            ),
            'kilometre_deviation_percent' => $this->percentage(
                abs($differenceKilometreHundredths),
                $this->plannedKilometreHundredths,
            ),
        ];
    }

    private function kilometreHundredths(mixed $value): int
    {
        return (int) round(
            (float) $value
            * self::HUNDREDTHS_PER_KILOMETRE,
            0,
            PHP_ROUND_HALF_UP,
        );
    }

    private function percentage(
        int $numerator,
        int $denominator,
    ): ?float {
        if ($denominator === 0) {
            return null;
        }

        return round(
            ($numerator / $denominator) * 100,
            2,
            PHP_ROUND_HALF_UP,
        );
    }

    private function formatKilometres(int $hundredths): string
    {
        return number_format(
            $hundredths
            / self::HUNDREDTHS_PER_KILOMETRE,
            2,
            '.',
            '',
        );
    }
}
