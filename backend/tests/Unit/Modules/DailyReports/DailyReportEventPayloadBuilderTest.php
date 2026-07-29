<?php

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use App\Modules\DailyReports\Services\DailyReportEventPayloadBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DailyReportEventPayloadBuilderTest extends TestCase
{
    public function test_it_builds_complete_transition_event_payload(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        self::assertSame(
            [
                'daily_report_id' => 1001,
                'organization_id' => 12,
                'event_type' => DailyReportEvent::TYPE_SUBMITTED,
                'from_status' => DailyReport::STATUS_DRAFT,
                'to_status' => DailyReport::STATUS_SUBMITTED,
                'acted_by_user_id' => 8,
                'reason' => 'Route completed.',
                'affected_fields' => [
                    'status',
                    'submitted_at',
                ],
                'metadata' => [
                    'source' => 'driver-portal',
                ],
            ],
            $builder->build(
                dailyReportId: 1001,
                organizationId: 12,
                eventType: DailyReportEvent::TYPE_SUBMITTED,
                actedByUserId: 8,
                fromStatus: DailyReport::STATUS_DRAFT,
                toStatus: DailyReport::STATUS_SUBMITTED,
                reason: '  Route completed.  ',
                affectedFields: [
                    'status',
                    'submitted_at',
                ],
                metadata: [
                    'source' => 'driver-portal',
                ],
            ),
        );
    }

    public function test_it_normalizes_empty_optional_values_to_null(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $payload = $builder->build(
            dailyReportId: 1001,
            organizationId: 12,
            eventType: DailyReportEvent::TYPE_CREATED,
            actedByUserId: 8,
            fromStatus: null,
            toStatus: DailyReport::STATUS_DRAFT,
            reason: '   ',
        );

        self::assertNull($payload['reason']);
        self::assertNull($payload['affected_fields']);
        self::assertNull($payload['metadata']);
    }

    public function test_it_rejects_unknown_event_type(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Unknown daily report event type "unknown".',
        );

        $builder->build(
            dailyReportId: 1001,
            organizationId: 12,
            eventType: 'unknown',
            actedByUserId: 8,
            fromStatus: null,
            toStatus: DailyReport::STATUS_DRAFT,
        );
    }

    public function test_it_rejects_unknown_status(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'From status "unknown" is unknown.',
        );

        $builder->build(
            dailyReportId: 1001,
            organizationId: 12,
            eventType: DailyReportEvent::TYPE_UPDATED,
            actedByUserId: 8,
            fromStatus: 'unknown',
            toStatus: DailyReport::STATUS_DRAFT,
        );
    }

    public function test_it_rejects_non_positive_identifier(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Daily report identifier must be a positive integer.',
        );

        $builder->build(
            dailyReportId: 0,
            organizationId: 12,
            eventType: DailyReportEvent::TYPE_CREATED,
            actedByUserId: 8,
            fromStatus: null,
            toStatus: DailyReport::STATUS_DRAFT,
        );
    }

    public function test_it_rejects_unknown_affected_field(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Unknown affected daily report field "unknown_field".',
        );

        $builder->build(
            dailyReportId: 1001,
            organizationId: 12,
            eventType: DailyReportEvent::TYPE_UPDATED,
            actedByUserId: 8,
            fromStatus: DailyReport::STATUS_DRAFT,
            toStatus: DailyReport::STATUS_DRAFT,
            affectedFields: [
                'unknown_field',
            ],
        );
    }

    public function test_it_rejects_duplicate_affected_field(): void
    {
        $builder = new DailyReportEventPayloadBuilder;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Affected daily report field "status" is duplicated.',
        );

        $builder->build(
            dailyReportId: 1001,
            organizationId: 12,
            eventType: DailyReportEvent::TYPE_UPDATED,
            actedByUserId: 8,
            fromStatus: DailyReport::STATUS_DRAFT,
            toStatus: DailyReport::STATUS_DRAFT,
            affectedFields: [
                'status',
                ' status ',
            ],
        );
    }
}
