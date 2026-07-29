<?php

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Exceptions\InvalidDailyReportTransition;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Services\DailyReportWorkflow;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DailyReportWorkflowTest extends TestCase
{
    /** @var array<string, list<string>> */
    private const EXPECTED_TRANSITIONS = [
        DailyReport::STATUS_DRAFT => [
            DailyReport::STATUS_SUBMITTED,
        ],
        DailyReport::STATUS_SUBMITTED => [
            DailyReport::STATUS_UNDER_REVIEW,
        ],
        DailyReport::STATUS_UNDER_REVIEW => [
            DailyReport::STATUS_CORRECTION_REQUESTED,
            DailyReport::STATUS_APPROVED,
        ],
        DailyReport::STATUS_CORRECTION_REQUESTED => [
            DailyReport::STATUS_CORRECTED,
        ],
        DailyReport::STATUS_CORRECTED => [
            DailyReport::STATUS_SUBMITTED,
        ],
        DailyReport::STATUS_APPROVED => [
            DailyReport::STATUS_CLOSED,
        ],
        DailyReport::STATUS_CLOSED => [],
    ];

    public function test_it_allows_every_supported_transition(): void
    {
        $workflow = new DailyReportWorkflow;

        foreach (self::EXPECTED_TRANSITIONS as $fromStatus => $toStatuses) {
            self::assertSame(
                $toStatuses,
                $workflow->allowedNextStatuses($fromStatus),
            );

            foreach ($toStatuses as $toStatus) {
                self::assertTrue(
                    $workflow->canTransition(
                        $fromStatus,
                        $toStatus,
                    ),
                );

                $workflow->assertCanTransition(
                    $fromStatus,
                    $toStatus,
                );
            }
        }
    }

    public function test_it_rejects_skipped_and_reverse_transitions(): void
    {
        $workflow = new DailyReportWorkflow;

        self::assertFalse(
            $workflow->canTransition(
                DailyReport::STATUS_DRAFT,
                DailyReport::STATUS_APPROVED,
            ),
        );

        self::assertFalse(
            $workflow->canTransition(
                DailyReport::STATUS_APPROVED,
                DailyReport::STATUS_UNDER_REVIEW,
            ),
        );

        self::assertFalse(
            $workflow->canTransition(
                DailyReport::STATUS_CLOSED,
                DailyReport::STATUS_DRAFT,
            ),
        );
    }

    public function test_it_rejects_transition_to_the_same_status(): void
    {
        $workflow = new DailyReportWorkflow;

        foreach (DailyReport::STATUSES as $status) {
            self::assertFalse(
                $workflow->canTransition(
                    $status,
                    $status,
                ),
            );
        }
    }

    public function test_closed_report_has_no_next_statuses(): void
    {
        $workflow = new DailyReportWorkflow;

        self::assertSame(
            [],
            $workflow->allowedNextStatuses(
                DailyReport::STATUS_CLOSED,
            ),
        );
    }

    public function test_unknown_status_cannot_transition(): void
    {
        $workflow = new DailyReportWorkflow;

        self::assertFalse(
            $workflow->canTransition(
                'unknown',
                DailyReport::STATUS_SUBMITTED,
            ),
        );

        self::assertFalse(
            $workflow->canTransition(
                DailyReport::STATUS_DRAFT,
                'unknown',
            ),
        );
    }

    public function test_allowed_next_statuses_rejects_unknown_status(): void
    {
        $workflow = new DailyReportWorkflow;

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Unknown daily report status "unknown".',
        );

        $workflow->allowedNextStatuses('unknown');
    }

    public function test_assertion_throws_domain_exception_for_invalid_transition(): void
    {
        $workflow = new DailyReportWorkflow;

        $this->expectException(
            InvalidDailyReportTransition::class,
        );

        $this->expectExceptionMessage(
            'Daily report transition from "draft" to "approved" is not allowed.',
        );

        $workflow->assertCanTransition(
            DailyReport::STATUS_DRAFT,
            DailyReport::STATUS_APPROVED,
        );
    }

    public function test_correction_cycle_can_be_repeated(): void
    {
        $workflow = new DailyReportWorkflow;

        $statuses = [
            DailyReport::STATUS_DRAFT,
            DailyReport::STATUS_SUBMITTED,
            DailyReport::STATUS_UNDER_REVIEW,
            DailyReport::STATUS_CORRECTION_REQUESTED,
            DailyReport::STATUS_CORRECTED,
            DailyReport::STATUS_SUBMITTED,
            DailyReport::STATUS_UNDER_REVIEW,
            DailyReport::STATUS_CORRECTION_REQUESTED,
            DailyReport::STATUS_CORRECTED,
            DailyReport::STATUS_SUBMITTED,
            DailyReport::STATUS_UNDER_REVIEW,
            DailyReport::STATUS_APPROVED,
            DailyReport::STATUS_CLOSED,
        ];

        for (
            $index = 0;
            $index < count($statuses) - 1;
            $index++
        ) {
            self::assertTrue(
                $workflow->canTransition(
                    $statuses[$index],
                    $statuses[$index + 1],
                ),
            );
        }
    }
}
