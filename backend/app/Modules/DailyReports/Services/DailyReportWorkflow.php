<?php

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Exceptions\InvalidDailyReportTransition;
use App\Modules\DailyReports\Models\DailyReport;
use InvalidArgumentException;

final class DailyReportWorkflow
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
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

    public function canTransition(
        string $fromStatus,
        string $toStatus,
    ): bool {
        if (
            ! $this->isKnownStatus($fromStatus)
            || ! $this->isKnownStatus($toStatus)
        ) {
            return false;
        }

        return in_array(
            $toStatus,
            self::TRANSITIONS[$fromStatus],
            true,
        );
    }

    public function assertCanTransition(
        string $fromStatus,
        string $toStatus,
    ): void {
        if ($this->canTransition($fromStatus, $toStatus)) {
            return;
        }

        throw InvalidDailyReportTransition::between(
            $fromStatus,
            $toStatus,
        );
    }

    /**
     * @return list<string>
     */
    public function allowedNextStatuses(
        string $fromStatus,
    ): array {
        if (! $this->isKnownStatus($fromStatus)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown daily report status "%s".',
                    $fromStatus,
                ),
            );
        }

        return self::TRANSITIONS[$fromStatus];
    }

    private function isKnownStatus(string $status): bool
    {
        return array_key_exists(
            $status,
            self::TRANSITIONS,
        );
    }
}
