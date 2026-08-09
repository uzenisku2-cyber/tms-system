<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Services\DailyReportCalculations;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DailyReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DailyReport) {
            throw new LogicException(
                'DailyReportResource requires a DailyReport model.',
            );
        }

        $dailyReport = $this->resource;
        $performedByDriver =
            $dailyReport->relationLoaded(
                'performedByDriver',
            )
                ? $dailyReport->getRelation(
                    'performedByDriver',
                )
                : null;
        $calculations = new DailyReportCalculations;

        $loadedParcels = $this->nullableInteger(
            $dailyReport->getAttribute('loaded_parcels'),
        );

        $deliveredParcels = $this->nullableInteger(
            $dailyReport->getAttribute('delivered_parcels'),
        );

        $redirectedParcels = $this->nullableInteger(
            $dailyReport->getAttribute('redirected_parcels'),
        );

        $undeliveredParcels = $this->nullableInteger(
            $dailyReport->getAttribute('undelivered_parcels'),
        );

        $plannedKm = $this->nullableFloat(
            $dailyReport->getAttribute('planned_km'),
        );

        $actualKm = $this->nullableFloat(
            $dailyReport->getAttribute('actual_km'),
        );

        $hasCompleteParcelCounts =
            $deliveredParcels !== null
            && $redirectedParcels !== null
            && $undeliveredParcels !== null;

        $hasCompleteParcelBalance =
            $loadedParcels !== null
            && $hasCompleteParcelCounts;

        $hasCompleteKilometres =
            $plannedKm !== null
            && $actualKm !== null;

        return [
            'public_id' => (string) $dailyReport->getAttribute(
                'public_id',
            ),
            'organization_id' => (int) $dailyReport->getAttribute(
                'organization_id',
            ),
            'trip_id' => $this->nullableInteger(
                $dailyReport->getAttribute('trip_id'),
            ),
            'performed_by_driver_id' => (int) $dailyReport->getAttribute(
                'performed_by_driver_id',
            ),
            'performed_by_driver_external_id' =>
                $performedByDriver?->getAttribute(
                    'external_driver_id',
                ),
            'performed_by_driver_name' =>
                $performedByDriver === null
                    ? null
                    : trim(
                        (string) $performedByDriver->getAttribute(
                            'last_name',
                        )
                        .' '.
                        (string) $performedByDriver->getAttribute(
                            'first_name',
                        ),
                    ),
            'vehicle_id' => $this->nullableInteger(
                $dailyReport->getAttribute('vehicle_id'),
            ),
            'entered_by_user_id' => (int) $dailyReport->getAttribute(
                'entered_by_user_id',
            ),
            'route_number' => (string) $dailyReport->getAttribute(
                'route_number',
            ),
            'service_date' => $this->formatTemporal(
                $dailyReport->getAttribute('service_date'),
                'Y-m-d',
            ),
            'daily_report_form_configuration_id' =>
                $this->nullableInteger(
                    $dailyReport->getAttribute(
                        'daily_report_form_configuration_id',
                    ),
                ),
            'custom_field_values' =>
                $dailyReport->getAttribute(
                    'custom_field_values',
                ) ?? [],
            'status' => (string) $dailyReport->getAttribute(
                'status',
            ),
            'entry_method' => (string) $dailyReport->getAttribute(
                'entry_method',
            ),
            'entered_on_behalf' => (bool) $dailyReport->getAttribute(
                'entered_on_behalf',
            ),
            'completion_confirmed_at' => $this->formatTemporal(
                $dailyReport->getAttribute(
                    'completion_confirmed_at',
                ),
                DATE_ATOM,
            ),
            'departure_time' => $this->nullableTime(
                $dailyReport->getAttribute(
                    'departure_time',
                ),
            ),
            'arrival_time' => $this->nullableTime(
                $dailyReport->getAttribute(
                    'arrival_time',
                ),
            ),
            'loaded_parcels' => $loadedParcels,
            'delivered_parcels' => $deliveredParcels,
            'redirected_parcels' => $redirectedParcels,
            'undelivered_parcels' => $undeliveredParcels,
            'planned_km' => $this->nullableDecimal(
                $dailyReport->getAttribute('planned_km'),
            ),
            'actual_km' => $this->nullableDecimal(
                $dailyReport->getAttribute('actual_km'),
            ),
            'actual_km_source' => $dailyReport->getAttribute(
                'actual_km_source',
            ),
            'surcharge_amount' => $this->nullableDecimal(
                $dailyReport->getAttribute('surcharge_amount'),
            ),
            'operational_notes' => $dailyReport->getAttribute(
                'operational_notes',
            ),
            'current_version' => (int) $dailyReport->getAttribute(
                'current_version',
            ),
            'submitted_at' => $this->formatTemporal(
                $dailyReport->getAttribute('submitted_at'),
                DATE_ATOM,
            ),
            'review_started_at' => $this->formatTemporal(
                $dailyReport->getAttribute('review_started_at'),
                DATE_ATOM,
            ),
            'reviewed_by_user_id' => $this->nullableInteger(
                $dailyReport->getAttribute('reviewed_by_user_id'),
            ),
            'approved_at' => $this->formatTemporal(
                $dailyReport->getAttribute('approved_at'),
                DATE_ATOM,
            ),
            'approved_by_user_id' => $this->nullableInteger(
                $dailyReport->getAttribute('approved_by_user_id'),
            ),
            'closed_at' => $this->formatTemporal(
                $dailyReport->getAttribute('closed_at'),
                DATE_ATOM,
            ),
            'calculated' => [
                'total_processed_parcels' => $hasCompleteParcelCounts
                        ? $calculations->totalProcessedParcels(
                            $deliveredParcels,
                            $redirectedParcels,
                            $undeliveredParcels,
                        )
                        : null,
                'not_delivered_parcels' =>
                    $hasCompleteParcelBalance
                        ? $calculations->notDeliveredParcels(
                            $loadedParcels,
                            $deliveredParcels,
                            $redirectedParcels,
                            $undeliveredParcels,
                        )
                        : null,
                'difference_km' => $hasCompleteKilometres
                        ? $calculations->differenceKm(
                            $plannedKm,
                            $actualKm,
                        )
                        : null,
                'deviation_percentage' => $hasCompleteKilometres
                        ? $calculations->deviationPercentage(
                            $plannedKm,
                            $actualKm,
                        )
                        : null,
                'requires_kilometre_attention' => $hasCompleteKilometres
                        ? $calculations->requiresKilometreAttention(
                            $plannedKm,
                            $actualKm,
                        )
                        : null,
            ],
            'created_at' => $this->formatTemporal(
                $dailyReport->getAttribute('created_at'),
                DATE_ATOM,
            ),
            'updated_at' => $this->formatTemporal(
                $dailyReport->getAttribute('updated_at'),
                DATE_ATOM,
            ),
        ];
    }

    private function nullableTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                'Daily report time resource value is invalid.',
            );
        }

        $normalized = trim($value);

        if (
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
                $normalized,
            ) !== 1
        ) {
            throw new LogicException(
                'Daily report time resource value is invalid.',
            );
        }

        return substr($normalized, 0, 5);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new LogicException(
                'Daily report integer resource value is invalid.',
            );
        }

        return (int) $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new LogicException(
                'Daily report decimal resource value is invalid.',
            );
        }

        return (float) $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $numericValue = $this->nullableFloat($value);

        if ($numericValue === null) {
            return null;
        }

        return number_format(
            $numericValue,
            2,
            '.',
            '',
        );
    }

    private function formatTemporal(
        mixed $value,
        string $format,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof DateTimeInterface) {
            throw new LogicException(
                'Daily report temporal resource value is invalid.',
            );
        }

        return $value->format($format);
    }
}
