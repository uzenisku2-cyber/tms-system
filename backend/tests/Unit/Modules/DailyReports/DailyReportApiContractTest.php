<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\DailyReportIndexRequest;
use App\Modules\DailyReports\Resources\DailyReportResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DailyReportApiContractTest extends TestCase
{
    public function test_index_request_accepts_supported_filters(): void
    {
        $validator = Validator::make(
            [
                'status' => DailyReport::STATUS_UNDER_REVIEW,
                'performed_by_driver_id' => 15,
                'service_date_from' => '2026-07-01',
                'service_date_to' => '2026-07-31',
                'route_number' => 'R-100',
                'sort_by' => 'service_date',
                'sort_dir' => 'desc',
                'per_page' => 50,
            ],
            (new DailyReportIndexRequest)->rules(),
        );

        self::assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function test_index_request_rejects_invalid_filters(): void
    {
        $validator = Validator::make(
            [
                'status' => 'unknown',
                'performed_by_driver_id' => 0,
                'service_date_from' => '2026-07-31',
                'service_date_to' => '2026-07-01',
                'sort_by' => 'internal_id',
                'sort_dir' => 'sideways',
                'per_page' => 101,
            ],
            (new DailyReportIndexRequest)->rules(),
        );

        self::assertTrue($validator->fails());

        $errors = $validator->errors();

        self::assertTrue($errors->has('status'));
        self::assertTrue($errors->has('performed_by_driver_id'));
        self::assertTrue($errors->has('service_date_to'));
        self::assertTrue($errors->has('sort_by'));
        self::assertTrue($errors->has('sort_dir'));
        self::assertTrue($errors->has('per_page'));
    }

    public function test_resource_exposes_stored_and_calculated_values(): void
    {
        $data = (new DailyReportResource(
            $this->dailyReport(),
        ))->resolve(
            Request::create('/api/v1/daily-reports/example'),
        );

        self::assertSame(
            '018f0b28-7d36-7e67-a48a-80d6f847ed21',
            $data['public_id'],
        );

        self::assertSame(10, $data['organization_id']);
        self::assertSame(22, $data['performed_by_driver_id']);
        self::assertSame('ROUTE-100', $data['route_number']);
        self::assertSame('2026-07-28', $data['service_date']);
        self::assertSame('100.00', $data['planned_km']);
        self::assertSame('112.50', $data['actual_km']);

        self::assertSame(
            [
                'total_processed_parcels' => 106,
                'not_delivered_parcels' => null,
                'difference_km' => 12.5,
                'deviation_percentage' => 12.5,
                'requires_kilometre_attention' => true,
            ],
            $data['calculated'],
        );

        self::assertSame(
            '2026-07-28T18:30:00+00:00',
            $data['completion_confirmed_at'],
        );

        self::assertSame(
            '2026-07-28T19:00:00+00:00',
            $data['created_at'],
        );
    }

    public function test_resource_marks_zero_planned_kilometres_for_review(): void
    {
        $data = (new DailyReportResource(
            $this->dailyReport([
                'planned_km' => '0.00',
                'actual_km' => '0.00',
            ]),
        ))->resolve(
            Request::create('/api/v1/daily-reports/example'),
        );

        self::assertSame(
            [
                'total_processed_parcels' => 106,
                'not_delivered_parcels' => null,
                'difference_km' => 0.0,
                'deviation_percentage' => null,
                'requires_kilometre_attention' => true,
            ],
            $data['calculated'],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function dailyReport(array $overrides = []): DailyReport
    {
        $dailyReport = new DailyReport;

        $dailyReport->forceFill(
            array_replace(
                [
                    'public_id' => '018f0b28-7d36-7e67-a48a-80d6f847ed21',
                    'organization_id' => 10,
                    'trip_id' => null,
                    'performed_by_driver_id' => 22,
                    'vehicle_id' => 33,
                    'entered_by_user_id' => 44,
                    'route_number' => 'ROUTE-100',
                    'route_number_normalized' => 'route-100',
                    'service_date' => '2026-07-28',
                    'status' => DailyReport::STATUS_UNDER_REVIEW,
                    'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
                    'entered_on_behalf' => false,
                    'completion_confirmed_at' => '2026-07-28T18:30:00+00:00',
                    'delivered_parcels' => 100,
                    'redirected_parcels' => 4,
                    'undelivered_parcels' => 2,
                    'planned_km' => '100.00',
                    'actual_km' => '112.50',
                    'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                    'operational_notes' => 'Route completed.',
                    'current_version' => 2,
                    'submitted_at' => '2026-07-28T18:40:00+00:00',
                    'review_started_at' => '2026-07-28T18:50:00+00:00',
                    'reviewed_by_user_id' => 55,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'closed_at' => null,
                    'created_at' => '2026-07-28T19:00:00+00:00',
                    'updated_at' => '2026-07-28T19:05:00+00:00',
                ],
                $overrides,
            ),
        );

        return $dailyReport;
    }
}
