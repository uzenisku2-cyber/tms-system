<?php

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Services\DailyReportSnapshotBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DailyReportSnapshotBuilderTest extends TestCase
{
    public function test_it_builds_complete_snapshot_in_fixed_order(): void
    {
        $builder = new DailyReportSnapshotBuilder;

        $attributes = array_reverse(
            $this->attributes(),
            true,
        );

        $snapshot = $builder->build($attributes);

        self::assertSame(
            DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
            array_keys($snapshot),
        );

        self::assertSame(
            'ROUTE-101',
            $snapshot['route_number'],
        );

        self::assertSame(
            41,
            $snapshot['performed_by_driver_id'],
        );

        self::assertNull(
            $snapshot['approved_at'],
        );
    }

    public function test_it_rejects_missing_snapshot_field(): void
    {
        $builder = new DailyReportSnapshotBuilder;
        $attributes = $this->attributes();

        unset($attributes['route_number']);

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Daily report snapshot field "route_number" is missing.',
        );

        $builder->build($attributes);
    }

    public function test_it_returns_changed_fields_in_snapshot_order(): void
    {
        $builder = new DailyReportSnapshotBuilder;

        $before = $this->attributes();
        $after = $before;

        $after['status'] = 'submitted';
        $after['delivered_parcels'] = 105;
        $after['operational_notes'] = null;

        self::assertSame(
            [
                'status',
                'delivered_parcels',
                'operational_notes',
            ],
            $builder->changedFields(
                $before,
                $after,
            ),
        );
    }

    public function test_it_returns_empty_list_when_nothing_changed(): void
    {
        $builder = new DailyReportSnapshotBuilder;
        $attributes = $this->attributes();

        self::assertSame(
            [],
            $builder->changedFields(
                $attributes,
                $attributes,
            ),
        );
    }

    public function test_changed_fields_uses_strict_comparison(): void
    {
        $builder = new DailyReportSnapshotBuilder;

        $before = $this->attributes();
        $after = $before;

        $after['planned_km'] = '125.5';

        self::assertSame(
            ['planned_km'],
            $builder->changedFields(
                $before,
                $after,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(): array
    {
        return [
            'public_id' => '6d44f04d-5658-466c-9cab-f62c469e549f',
            'organization_id' => 12,
            'trip_id' => null,
            'performed_by_driver_id' => 41,
            'vehicle_id' => 19,
            'entered_by_user_id' => 8,
            'route_number' => 'ROUTE-101',
            'route_number_normalized' => 'route-101',
            'service_date' => '2026-07-26',
            'status' => 'draft',
            'entry_method' => 'driver',
            'entered_on_behalf' => false,
            'completion_confirmed_at' => '2026-07-26T18:30:00+00:00',
            'delivered_parcels' => 100,
            'redirected_parcels' => 4,
            'undelivered_parcels' => 2,
            'planned_km' => '125.50',
            'actual_km' => '130.25',
            'actual_km_source' => 'delivery_application',
            'operational_notes' => 'Route completed.',
            'current_version' => 1,
            'submitted_at' => null,
            'review_started_at' => null,
            'reviewed_by_user_id' => null,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'closed_at' => null,
        ];
    }
}
