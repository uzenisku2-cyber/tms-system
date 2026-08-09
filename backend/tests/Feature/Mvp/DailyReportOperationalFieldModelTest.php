<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Modules\DailyReports\Requests\DailyReportRequestRules;
use App\Modules\DailyReports\Services\DailyReportSnapshotBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class DailyReportOperationalFieldModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_columns_and_request_contract_exist(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'daily_reports',
                [
                    'departure_time',
                    'arrival_time',
                    'loaded_parcels',
                    'surcharge_amount',
                ],
            ),
        );

        $rules = DailyReportRequestRules::creation();

        foreach ([
            'departure_time',
            'arrival_time',
            'loaded_parcels',
            'surcharge_amount',
        ] as $field) {
            self::assertArrayHasKey($field, $rules);
        }

        $validator = Validator::make(
            [
                'performed_by_driver_id' => 1,
                'route_number' => 'R-100',
                'service_date' => '2025-06-01',
                'departure_time' => '08:15',
                'arrival_time' => '16:45',
                'loaded_parcels' => 120,
                'delivered_parcels' => 90,
                'redirected_parcels' => 20,
                'undelivered_parcels' => 10,
                'planned_km' => '100.00',
                'actual_km' => '104.50',
                'actual_km_source' => 'manual',
                'surcharge_amount' => '0.00',
            ],
            $rules,
        );

        self::assertFalse(
            $validator->fails(),
            implode(
                ' ',
                $validator->errors()->all(),
            ),
        );
    }

    public function test_snapshot_has_neutral_defaults_for_new_fields(): void
    {
        $builder = new DailyReportSnapshotBuilder;

        $snapshot = $builder->build([
            'public_id' => '11111111-1111-4111-8111-111111111111',
            'organization_id' => 1,
            'trip_id' => null,
            'performed_by_driver_id' => 1,
            'vehicle_id' => null,
            'entered_by_user_id' => 1,
            'route_number' => 'R-100',
            'route_number_normalized' => 'r-100',
            'service_date' => '2025-06-01',
            'status' => 'draft',
            'entry_method' => 'driver',
            'entered_on_behalf' => false,
            'completion_confirmed_at' => null,
            'delivered_parcels' => 90,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 10,
            'planned_km' => '100.00',
            'actual_km' => '104.50',
            'actual_km_source' => 'manual',
            'operational_notes' => null,
            'current_version' => 1,
            'submitted_at' => null,
            'review_started_at' => null,
            'reviewed_by_user_id' => null,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'closed_at' => null,
        ]);

        self::assertNull($snapshot['departure_time']);
        self::assertNull($snapshot['arrival_time']);
        self::assertNull($snapshot['loaded_parcels']);
        self::assertSame(
            '0.00',
            $snapshot['surcharge_amount'],
        );
    }

    public function test_settings_page_contains_real_twelve_field_definition(): void
    {
        $this->get('/daily-report-settings')
            ->assertOk()
            ->assertSee('Datum')
            ->assertSee('Trasa č.')
            ->assertSee('Čas odjezdu')
            ->assertSee('Čas příjezdu')
            ->assertSee('Trasa naměřená')
            ->assertSee('Trasa plánovaná')
            ->assertSee('Naloženo ks')
            ->assertSee('Doručeno na adresu')
            ->assertSee('Doručeno na výdejní místo')
            ->assertSee('Odmítnuté ks')
            ->assertSee('Příplatek')
            ->assertSee('Poznámka');
    }
}
