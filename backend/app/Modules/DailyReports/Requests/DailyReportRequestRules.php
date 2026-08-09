<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Models\DailyReport;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class DailyReportRequestRules
{
    /** @var list<string> */
    public const MUTABLE_FIELDS = [
        'route_number',
        'service_date',
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
        'custom_field_values',
    ];

    /**
     * @return array<string, list<mixed>>
     */
    public static function creation(): array
    {
        return [
            'performed_by_driver_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'trip_id' => [
                'prohibited',
            ],
            'vehicle_id' => [
                'prohibited',
            ],
            'route_number' => [
                'required',
                'string',
                'max:100',
            ],
            'service_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'completion_confirmed_at' => [
                'nullable',
                'date',
            ],
            'departure_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'arrival_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'loaded_parcels' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'delivered_parcels' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'redirected_parcels' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'undelivered_parcels' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'planned_km' => [
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'actual_km' => [
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'actual_km_source' => [
                'nullable',
                'string',
                Rule::in(DailyReport::ACTUAL_KM_SOURCES),
            ],
            'surcharge_amount' => [
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'operational_notes' => [
                'nullable',
                'string',
            ],
            'custom_field_values' => [
                'nullable',
                'array',
            ],
            'reason' => self::reason(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function mutation(): array
    {
        return [
            'expected_version' => self::expectedVersion(),
            'route_number' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'service_date' => [
                'sometimes',
                'date_format:Y-m-d',
            ],
            'completion_confirmed_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'departure_time' => [
                'sometimes',
                'nullable',
                'date_format:H:i',
            ],
            'arrival_time' => [
                'sometimes',
                'nullable',
                'date_format:H:i',
            ],
            'loaded_parcels' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'delivered_parcels' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'redirected_parcels' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'undelivered_parcels' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'planned_km' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'actual_km' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'actual_km_source' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(DailyReport::ACTUAL_KM_SOURCES),
            ],
            'surcharge_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'between:0,99999999.99',
            ],
            'operational_notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'reason' => self::reason(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function transition(): array
    {
        return [
            'expected_version' => self::expectedVersion(),
            'reason' => self::reason(),
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function expectedVersion(): array
    {
        return [
            'required',
            'integer',
            'min:1',
        ];
    }

    /**
     * @return list<mixed>
     */
    private static function reason(): array
    {
        return [
            'nullable',
            'string',
            'max:4000',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function validateParcelBalance(
        array $input,
        Validator $validator,
    ): void {
        $keys = [
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
        ];

        foreach ($keys as $key) {
            if (
                ! array_key_exists($key, $input)
                || $input[$key] === null
                || $input[$key] === ''
                || ! is_numeric($input[$key])
            ) {
                return;
            }
        }

        $notDelivered =
            (int) $input['loaded_parcels']
            - (int) $input['delivered_parcels']
            - (int) $input['redirected_parcels']
            - (int) $input['undelivered_parcels'];

        if ($notDelivered >= 0) {
            return;
        }

        $validator->errors()->add(
            'parcel_balance',
            sprintf(
                'Chyba v zápisu: doručeno na adresu + výdejní místo + odmítnuto zákazníkem převyšuje počet naložených zásilek o %d ks.',
                abs($notDelivered),
            ),
        );
    }
}
