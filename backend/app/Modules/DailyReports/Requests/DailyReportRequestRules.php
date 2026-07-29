<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Models\DailyReport;
use Illuminate\Validation\Rule;

final class DailyReportRequestRules
{
    /** @var list<string> */
    public const MUTABLE_FIELDS = [
        'route_number',
        'service_date',
        'completion_confirmed_at',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'operational_notes',
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
            'operational_notes' => [
                'nullable',
                'string',
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
}
