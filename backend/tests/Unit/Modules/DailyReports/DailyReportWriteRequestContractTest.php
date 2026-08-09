<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\DailyReportRequestRules;
use App\Modules\DailyReports\Requests\DailyReportTransitionRequest;
use App\Modules\DailyReports\Requests\RecordDailyReportCorrectionRequest;
use App\Modules\DailyReports\Requests\StoreDailyReportRequest;
use App\Modules\DailyReports\Requests\UpdateDailyReportRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;
use Tests\TestCase;

final class DailyReportWriteRequestContractTest extends TestCase
{
    public function test_store_request_accepts_supported_input(): void
    {
        $validator = $this->validator(
            new StoreDailyReportRequest,
            [
                'performed_by_driver_id' => 15,
                'route_number' => 'ROUTE-100',
                'service_date' => '2026-07-29',
                'completion_confirmed_at' => '2026-07-29T18:30:00+00:00',
                'delivered_parcels' => 100,
                'redirected_parcels' => 4,
                'undelivered_parcels' => 2,
                'planned_km' => '100.00',
                'actual_km' => '109.50',
                'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                'operational_notes' => 'Route completed.',
                'reason' => 'Initial report entry.',
            ],
        );

        self::assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function test_store_request_requires_actual_km_pair(): void
    {
        $validator = $this->validator(
            new StoreDailyReportRequest,
            [
                'performed_by_driver_id' => 15,
                'route_number' => 'ROUTE-100',
                'service_date' => '2026-07-29',
                'actual_km' => '109.50',
            ],
        );

        self::assertTrue($validator->fails());

        self::assertTrue(
            $validator->errors()->has('actual_km_source'),
        );
    }

    public function test_update_request_requires_mutable_input(): void
    {
        $validator = $this->validator(
            new UpdateDailyReportRequest,
            [
                'expected_version' => 2,
                'reason' => 'No operational change.',
            ],
        );

        self::assertTrue($validator->fails());

        self::assertTrue(
            $validator->errors()->has('attributes'),
        );
    }

    public function test_update_request_accepts_intentional_null(): void
    {
        $validator = $this->validator(
            new UpdateDailyReportRequest,
            [
                'expected_version' => 2,
                'operational_notes' => null,
                'reason' => 'Remove obsolete note.',
            ],
        );

        self::assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function test_correction_request_requires_corrected_field(): void
    {
        $validator = $this->validator(
            new RecordDailyReportCorrectionRequest,
            [
                'expected_version' => 3,
            ],
        );

        self::assertTrue($validator->fails());

        self::assertTrue(
            $validator->errors()->has('attributes'),
        );
    }

    public function test_transition_request_validates_expected_version(): void
    {
        $valid = $this->validator(
            new DailyReportTransitionRequest,
            [
                'expected_version' => 3,
                'reason' => 'Reviewed.',
            ],
        );

        self::assertFalse($valid->fails());

        $invalid = $this->validator(
            new DailyReportTransitionRequest,
            [
                'expected_version' => 0,
            ],
        );

        self::assertTrue($invalid->fails());

        self::assertTrue(
            $invalid->errors()->has('expected_version'),
        );
    }

    public function test_actor_and_workflow_fields_are_server_controlled(): void
    {
        $forbiddenClientFields = [
            'organization_id',
            'entered_by_user_id',
            'entry_method',
            'entered_on_behalf',
            'status',
            'current_version',
            'reviewed_by_user_id',
            'approved_by_user_id',
            'submitted_at',
            'review_started_at',
            'approved_at',
            'closed_at',
        ];

        foreach ([
            (new StoreDailyReportRequest)->rules(),
            (new UpdateDailyReportRequest)->rules(),
            (new RecordDailyReportCorrectionRequest)->rules(),
            (new DailyReportTransitionRequest)->rules(),
        ] as $rules) {
            foreach ($forbiddenClientFields as $field) {
                self::assertArrayNotHasKey(
                    $field,
                    $rules,
                );
            }
        }
    }

    public function test_mutable_field_contract_matches_service_boundary(): void
    {
        self::assertSame(
            [
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
            ],
            DailyReportRequestRules::MUTABLE_FIELDS,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validator(
        StoreDailyReportRequest
            |UpdateDailyReportRequest
            |RecordDailyReportCorrectionRequest
            |DailyReportTransitionRequest $request,
        array $input,
    ): LaravelValidator {
        $request->replace($input);

        $validator = Validator::make(
            $input,
            $request->rules(),
        );

        if (
            $request instanceof StoreDailyReportRequest
            || $request instanceof UpdateDailyReportRequest
            || $request instanceof RecordDailyReportCorrectionRequest
        ) {
            $request->withValidator($validator);
        }

        return $validator;
    }
}
