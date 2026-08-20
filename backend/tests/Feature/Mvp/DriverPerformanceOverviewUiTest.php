<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DriverPerformanceOverviewUiTest extends TestCase
{
    public function test_statistics_page_uses_canonical_raw_performance_overview(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach (
            [
                'S026-04A FILTERED DRIVER PERFORMANCE OVERVIEW',
                '/api/v1/daily-reports/performance-overview?',
                "query.set(\n            'group_by'",
                "'performed_by_driver_id'",
                "'carrier_scope'",
                "'carrier_organization_id'",
                "'period'",
                "'service_date_from'",
                "'service_date_to'",
                "period: 'current_month'",
                'drayviaDriverStatisticsQuickPeriods',
                'drayviaDriverStatisticsCarrier',
                'current_month',
                'previous_month',
                'current_year',
                'previous_year',
                'last_12_months',
                'all_history',
                'data?.filter_options',
                'filterOptions.carriers',
                'customer_rejected_parcels',
                'not_delivered_parcels',
                'processed_share_percent',
                'parcel_complete_route_count',
                'kilometre_complete_route_count',
                'quality_profile_applied',
                'data?.timeline',
                'drayviaDriverStatisticsTimelineRows',
                'D&#205;L&#268;&#205; KVALITA',
                'Bez historicky doloženého dopravce',
                'maximumFractionDigits: 0',
            ] as $marker
        ) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                '/api/v1/daily-reports/performance-overview?',
            ),
        );

        foreach (
            [
                'fetchAllDriverStatisticsReports',
                '/api/v1/daily-reports?per_page=',
                'partialQuality',
            ] as $obsoleteMarker
        ) {
            self::assertStringNotContainsString(
                $obsoleteMarker,
                $source,
            );
        }
    }
}
