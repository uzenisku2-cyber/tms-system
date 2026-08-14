<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Pricing\Services\FinancialCalculationSnapshotBuilder;
use Carbon\CarbonImmutable;
use DomainException;
use Tests\TestCase;

final class FinancialCalculationSnapshotBuilderTest extends TestCase
{
    public function test_it_builds_a_deterministic_snapshot_from_an_approved_version(): void
    {
        $dailyReportVersion = $this->dailyReportVersion(
            snapshot: $this->sourceSnapshot(
                status: DailyReport::STATUS_APPROVED,
                currentVersion: 4,
                closedAt: null,
            ),
            versionNumber: 4,
        );

        $capturedAt = CarbonImmutable::parse(
            '2026-07-29 21:00:00',
            'Europe/Prague',
        );

        $snapshot = $this->builder()->build(
            $dailyReportVersion,
            $capturedAt,
        );

        self::assertSame(
            FinancialCalculationSnapshotBuilder::SNAPSHOT_FIELDS,
            array_keys($snapshot),
        );

        self::assertSame(
            [
                'daily_report_id' => 41,
                'daily_report_version' => 4,
                'public_id' => '11111111-2222-4333-8444-555555555555',

                'organization_id' => 7,
                'trip_id' => null,
                'performed_by_driver_id' => 12,
                'vehicle_id' => null,
                'route_number' => 'ROUTE-401',
                'route_number_normalized' => 'route-401',
                'service_date' => '2026-07-28',
                'status' => DailyReport::STATUS_APPROVED,
                'loaded_parcels' => 28,
                'delivered_parcels' => 25,
                'redirected_parcels' => 2,
                'undelivered_parcels' => 1,
                'customer_rejected_parcels' => 1,
                'not_delivered_parcels' => 0,
                'processed_parcels' => 28,
                'planned_km' => '105.00',
                'actual_km' => '108.50',
                'actual_km_source' => 'delivery_app',
                'approved_at' => '2026-07-29 18:00:00',
                'approved_by_user_id' => 99,
                'closed_at' => null,
                'captured_at' => '2026-07-29 21:00:00',
            ],
            $snapshot,
        );
    }

    public function test_it_supports_a_closed_daily_report_snapshot(): void
    {
        $dailyReportVersion = $this->dailyReportVersion(
            snapshot: $this->sourceSnapshot(
                status: DailyReport::STATUS_CLOSED,
                currentVersion: 5,
                closedAt: '2026-07-29 20:30:00',
            ),
            versionNumber: 5,
        );

        $snapshot = $this->builder()->build(
            $dailyReportVersion,
            CarbonImmutable::parse(
                '2026-07-29 21:05:00',
                'Europe/Prague',
            ),
        );

        self::assertSame(
            DailyReport::STATUS_CLOSED,
            $snapshot['status'],
        );

        self::assertSame(
            '2026-07-29 20:30:00',
            $snapshot['closed_at'],
        );

        self::assertSame(
            5,
            $snapshot['daily_report_version'],
        );

        self::assertSame(
            '2026-07-29 21:05:00',
            $snapshot['captured_at'],
        );
    }

    public function test_it_rejects_a_non_final_daily_report_status(): void
    {
        $dailyReportVersion = $this->dailyReportVersion(
            snapshot: $this->sourceSnapshot(
                status: DailyReport::STATUS_UNDER_REVIEW,
                currentVersion: 4,
                closedAt: null,
            ),
            versionNumber: 4,
        );

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            (
                'Financial calculation requires an approved '.
                'or closed daily-report snapshot; '.
                'status [under_review] given.'
            ),
        );

        $this->builder()->build(
            $dailyReportVersion,
            CarbonImmutable::parse(
                '2026-07-29 21:10:00',
                'Europe/Prague',
            ),
        );
    }

    public function test_it_rejects_a_snapshot_version_conflict(): void
    {
        $dailyReportVersion = $this->dailyReportVersion(
            snapshot: $this->sourceSnapshot(
                status: DailyReport::STATUS_APPROVED,
                currentVersion: 3,
                closedAt: null,
            ),
            versionNumber: 4,
        );

        $this->expectException(DomainException::class);

        $this->expectExceptionMessage(
            (
                'Daily-report snapshot version conflict: '.
                'stored version 4, snapshot version 3.'
            ),
        );

        $this->builder()->build(
            $dailyReportVersion,
            CarbonImmutable::parse(
                '2026-07-29 21:15:00',
                'Europe/Prague',
            ),
        );
    }

    private function builder(): FinancialCalculationSnapshotBuilder
    {
        return new FinancialCalculationSnapshotBuilder;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function dailyReportVersion(
        array $snapshot,
        int $versionNumber,
    ): DailyReportVersion {
        $dailyReportVersion = new DailyReportVersion;

        $dailyReportVersion->setRawAttributes(
            [
                'id' => 501,
                'daily_report_id' => 41,
                'version_number' => $versionNumber,
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            ],
            true,
        );

        return $dailyReportVersion;
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceSnapshot(
        string $status,
        int $currentVersion,
        ?string $closedAt,
    ): array {
        return [
            'public_id' => '11111111-2222-4333-8444-555555555555',

            'organization_id' => 7,
            'trip_id' => null,
            'performed_by_driver_id' => 12,
            'vehicle_id' => null,
            'route_number' => 'ROUTE-401',
            'route_number_normalized' => 'route-401',
            'service_date' => '2026-07-28',
            'status' => $status,
            'loaded_parcels' => 28,
            'delivered_parcels' => 25,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 1,
            'planned_km' => '105.00',
            'actual_km' => '108.50',
            'actual_km_source' => 'delivery_app',
            'current_version' => $currentVersion,
            'approved_at' => '2026-07-29 18:00:00',
            'approved_by_user_id' => 99,
            'closed_at' => $closedAt,
        ];
    }
}
