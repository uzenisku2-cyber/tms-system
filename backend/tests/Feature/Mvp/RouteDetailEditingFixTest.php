<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\DailyReportRequestRules;
use App\Modules\DailyReports\Resources\DailyReportResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class RouteDetailEditingFixTest extends TestCase
{
    public function test_resource_time_values_match_route_edit_validation_contract(): void
    {
        $report = new DailyReport;

        $report->forceFill([
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'organization_id' => 1,
            'performed_by_driver_id' => 4,
            'vehicle_id' => null,
            'entered_by_user_id' => 8,
            'route_number' => '35',
            'service_date' => '2025-06-06',
            'daily_report_form_configuration_id' => null,
            'custom_field_values' => [],
            'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => null,
            'departure_time' => '09:57:00',
            'arrival_time' => '17:20:00',
            'loaded_parcels' => 94,
            'delivered_parcels' => 89,
            'redirected_parcels' => 4,
            'undelivered_parcels' => 0,
            'planned_km' => '235.00',
            'actual_km' => '231.00',
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

        $resource = new DailyReportResource($report);

        $data = $resource->toArray(
            Request::create('/api/v1/daily-reports/example'),
        );

        self::assertSame(
            '09:57',
            $data['departure_time'],
        );

        self::assertSame(
            '17:20',
            $data['arrival_time'],
        );

        $validator = Validator::make(
            [
                'expected_version' => $data['current_version'],
                'route_number' => $data['route_number'],
                'departure_time' => $data['departure_time'],
                'arrival_time' => $data['arrival_time'],
                'loaded_parcels' => $data['loaded_parcels'],
                'delivered_parcels' => $data['delivered_parcels'],
                'redirected_parcels' => $data['redirected_parcels'],
                'undelivered_parcels' => $data['undelivered_parcels'],
                'planned_km' => $data['planned_km'],
                'actual_km' => $data['actual_km'],
                'actual_km_source' => $data['actual_km_source'],
                'surcharge_amount' => $data['surcharge_amount'],
                'operational_notes' => $data['operational_notes'],
            ],
            DailyReportRequestRules::mutation(),
        );

        self::assertFalse(
            $validator->fails(),
            json_encode(
                $validator->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    public function test_route_detail_uses_route_language_and_edit_time_normalization(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Detail trasy')
            ->assertSee('Načtené trasy')
            ->assertSee(
                'normalizeRouteEditValue',
                false,
            )
            ->assertSee(
                'value.slice(0, 5)',
                false,
            )
            ->assertSee(
                'Trasu se nepodařilo uložit:',
                false,
            )
            ->assertDontSee('Nový denní výkaz');
    }
}
