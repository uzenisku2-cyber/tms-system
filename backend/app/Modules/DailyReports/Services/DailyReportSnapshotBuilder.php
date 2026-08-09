<?php

namespace App\Modules\DailyReports\Services;

use InvalidArgumentException;

final class DailyReportSnapshotBuilder
{
    /** @var list<string> */
    public const SNAPSHOT_FIELDS = [
        'public_id',
        'organization_id',
        'trip_id',
        'performed_by_driver_id',
        'vehicle_id',
        'entered_by_user_id',
        'route_number',
        'route_number_normalized',
        'service_date',
        'daily_report_form_configuration_id',
        'custom_field_values',
        'status',
        'entry_method',
        'entered_on_behalf',
        'completion_confirmed_at',
        'departure_time',
        'arrival_time',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'surcharge_amount',
        'operational_notes',
        'current_version',
        'submitted_at',
        'review_started_at',
        'reviewed_by_user_id',
        'approved_at',
        'approved_by_user_id',
        'closed_at',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function build(array $attributes): array
    {
        $snapshot = [];

        $backwardCompatibleDefaults = [
            'daily_report_form_configuration_id' => null,
            'custom_field_values' => [],
            'departure_time' => null,
            'arrival_time' => null,
            'loaded_parcels' => null,
            'surcharge_amount' => '0.00',
        ];

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (! array_key_exists($field, $attributes)) {
                if (
                    array_key_exists(
                        $field,
                        $backwardCompatibleDefaults,
                    )
                ) {
                    $snapshot[$field] =
                        $backwardCompatibleDefaults[$field];

                    continue;
                }

                throw new InvalidArgumentException(
                    sprintf(
                        'Daily report snapshot field "%s" is missing.',
                        $field,
                    ),
                );
            }

            $snapshot[$field] = $attributes[$field];
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $beforeAttributes
     * @param  array<string, mixed>  $afterAttributes
     * @return list<string>
     */
    public function changedFields(
        array $beforeAttributes,
        array $afterAttributes,
    ): array {
        $beforeSnapshot = $this->build($beforeAttributes);
        $afterSnapshot = $this->build($afterAttributes);

        $changedFields = [];

        foreach (self::SNAPSHOT_FIELDS as $field) {
            if (
                $beforeSnapshot[$field]
                !== $afterSnapshot[$field]
            ) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }
}
