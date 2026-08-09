<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Modules\DailyReports\Requests\DailyReportIndexRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class RouteHistoryNavigationUiTest extends TestCase
{
    public function test_business_status_group_filter_is_validated(): void
    {
        $valid = Validator::make(
            [
                'status_group' => 'waiting',
                'service_date_from' =>
                    '2025-06-01',
                'service_date_to' =>
                    '2025-06-30',
            ],
            (new DailyReportIndexRequest)->rules(),
        );

        self::assertFalse(
            $valid->fails(),
            json_encode(
                $valid->errors()->toArray(),
                JSON_THROW_ON_ERROR,
            ),
        );

        $invalid = Validator::make(
            [
                'status_group' => 'unknown',
            ],
            (new DailyReportIndexRequest)->rules(),
        );

        self::assertTrue($invalid->fails());
        self::assertTrue(
            $invalid->errors()->has(
                'status_group',
            ),
        );
    }

    public function test_route_history_ui_exposes_fast_navigation_and_business_labels(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Rok')
            ->assertSee('Měsíc')
            ->assertSee('Rychlé období')
            ->assertSee('Vlastní období')
            ->assertSee('Stav trasy')
            ->assertSee(
                'routeYearButtons',
                false,
            )
            ->assertSee(
                'routeMonthButtons',
                false,
            )
            ->assertSee(
                'routeQuickPeriodButtons',
                false,
            )
            ->assertSee(
                'routeStatusButtons',
                false,
            )
            ->assertSee(
                'status_group',
                false,
            )
            ->assertSee(
                'initializeRouteHistoryPeriod',
                false,
            )
            ->assertSee(
                'Odmítnuto zákazníkem',
            );
    }
}