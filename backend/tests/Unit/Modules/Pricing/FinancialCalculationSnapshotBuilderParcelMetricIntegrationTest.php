<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Pricing\Services\FinancialCalculationSnapshotBuilder;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FinancialCalculationSnapshotBuilderParcelMetricIntegrationTest extends TestCase
{
    public function test_builder_exposes_explicit_parcel_business_metrics(): void
    {
        $version = new DailyReportVersion;

        $version->forceFill([
            'daily_report_id' => 900000001,
            'version_number' => 1,
            'snapshot' => [
                'current_version' => 1,
                'public_id' => '90000000-0000-4000-8000-000000000001',
                'organization_id' => 1,
                'trip_id' => null,
                'performed_by_driver_id' => 4,
                'vehicle_id' => null,
                'route_number' => '35',
                'route_number_normalized' => '35',
                'service_date' => '2026-08-01',
                'status' => DailyReport::STATUS_APPROVED,
                'loaded_parcels' => 100,
                'delivered_parcels' => 70,
                'redirected_parcels' => 20,
                'undelivered_parcels' => 3,
                'planned_km' => '100.00',
                'actual_km' => '101.00',
                'actual_km_source' => 'manual',
                'approved_at' => '2026-08-01 18:00:00',
                'approved_by_user_id' => 7,
                'closed_at' => null,
            ],
        ]);

        $snapshot = (new FinancialCalculationSnapshotBuilder)->build(
            $version,
            new DateTimeImmutable('2026-08-12 18:00:00'),
        );

        self::assertSame(100, $snapshot['loaded_parcels']);
        self::assertSame(70, $snapshot['delivered_parcels']);
        self::assertSame(20, $snapshot['redirected_parcels']);
        self::assertSame(3, $snapshot['undelivered_parcels']);

        self::assertSame(
            3,
            $snapshot['customer_rejected_parcels'],
        );

        self::assertSame(
            93,
            $snapshot['processed_parcels'],
        );

        self::assertSame(
            7,
            $snapshot['not_delivered_parcels'],
        );
    }
}
