<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\RecordDailyReportCorrectionRequest;
use App\Modules\DailyReports\Requests\StoreDailyReportRequest;
use App\Modules\DailyReports\Requests\UpdateDailyReportRequest;
use App\Modules\DailyReports\Resources\DailyReportResource;
use App\Modules\DailyReports\Services\DailyReportCalculations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;
use Tests\TestCase;

final class RouteParcelBalanceTest extends TestCase
{
    public function test_not_delivered_is_derived_from_loaded_and_terminal_outcomes(): void
    {
        $calculations = new DailyReportCalculations;

        self::assertSame(
            5,
            $calculations->notDeliveredParcels(
                100,
                80,
                10,
                5,
            ),
        );

        self::assertSame(
            -3,
            $calculations->notDeliveredParcels(
                100,
                90,
                8,
                5,
            ),
        );
    }

    public function test_create_update_and_correction_reject_negative_parcel_balance(): void
    {
        foreach ([
            new StoreDailyReportRequest,
            new UpdateDailyReportRequest,
            new RecordDailyReportCorrectionRequest,
        ] as $request) {
            $input = [
                'loaded_parcels' => 100,
                'delivered_parcels' => 90,
                'redirected_parcels' => 8,
                'undelivered_parcels' => 5,
            ];

            if ($request instanceof StoreDailyReportRequest) {
                $input += [
                    'performed_by_driver_id' => 1,
                    'route_number' => 'R-BALANCE',
                    'service_date' => '2025-06-01',
                ];
            } else {
                $input += [
                    'expected_version' => 1,
                ];
            }

            $validator = $this->validator(
                $request,
                $input,
            );

            self::assertTrue($validator->fails());
            self::assertTrue(
                $validator->errors()->has(
                    'parcel_balance',
                ),
            );
        }
    }

    public function test_api_resource_exposes_derived_not_delivered_value(): void
    {
        $report = new DailyReport;

        $report->forceFill([
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'organization_id' => 1,
            'performed_by_driver_id' => 1,
            'vehicle_id' => null,
            'entered_by_user_id' => 1,
            'route_number' => 'R-100',
            'service_date' => '2025-06-01',
            'daily_report_form_configuration_id' => null,
            'custom_field_values' => [],
            'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => null,
            'departure_time' => '08:00',
            'arrival_time' => '16:00',
            'loaded_parcels' => 100,
            'delivered_parcels' => 80,
            'redirected_parcels' => 10,
            'undelivered_parcels' => 5,
            'planned_km' => '100.00',
            'actual_km' => '100.00',
            'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_MANUAL,
            'surcharge_amount' => '0.00',
            'operational_notes' => null,
            'current_version' => 1,
            'submitted_at' => null,
            'review_started_at' => null,
            'reviewed_by_user_id' => null,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'closed_at' => null,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $data = (new DailyReportResource($report))
            ->toArray(
                Request::create(
                    '/api/v1/daily-reports/example',
                ),
            );

        self::assertSame(
            5,
            $data['calculated'][
                'not_delivered_parcels'
            ],
        );
    }

    public function test_route_ui_uses_customer_rejected_and_derived_not_delivered_labels(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Odmítnuto zákazníkem')
            ->assertSee('Nedoručeno')
            ->assertSee(
                'not_delivered_parcels',
                false,
            )
            ->assertSee(
                'currentRouteParcelBalance',
                false,
            )
            ->assertSee(
                'Chyba v zápisu',
            );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validator(
        FormRequest $request,
        array $input,
    ): LaravelValidator {
        $request->replace($input);

        $validator = Validator::make(
            $input,
            $request->rules(),
        );

        $request->withValidator($validator);

        return $validator;
    }
}
