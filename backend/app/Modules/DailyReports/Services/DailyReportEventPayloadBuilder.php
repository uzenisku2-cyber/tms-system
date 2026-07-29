<?php

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportEvent;
use InvalidArgumentException;

final class DailyReportEventPayloadBuilder
{
    /**
     * @param  list<string>  $affectedFields
     * @param  array<string, mixed>  $metadata
     * @return array{
     *     daily_report_id: int,
     *     organization_id: int,
     *     event_type: string,
     *     from_status: string|null,
     *     to_status: string|null,
     *     acted_by_user_id: int,
     *     reason: string|null,
     *     affected_fields: list<string>|null,
     *     metadata: array<string, mixed>|null
     * }
     */
    public function build(
        int $dailyReportId,
        int $organizationId,
        string $eventType,
        int $actedByUserId,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason = null,
        array $affectedFields = [],
        array $metadata = [],
    ): array {
        $this->assertPositiveIdentifier(
            $dailyReportId,
            'Daily report identifier',
        );

        $this->assertPositiveIdentifier(
            $organizationId,
            'Organization identifier',
        );

        $this->assertPositiveIdentifier(
            $actedByUserId,
            'Acting user identifier',
        );

        if (
            ! in_array(
                $eventType,
                DailyReportEvent::TYPES,
                true,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown daily report event type "%s".',
                    $eventType,
                ),
            );
        }

        $this->assertKnownStatus(
            $fromStatus,
            'From status',
        );

        $this->assertKnownStatus(
            $toStatus,
            'To status',
        );

        return [
            'daily_report_id' => $dailyReportId,
            'organization_id' => $organizationId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'acted_by_user_id' => $actedByUserId,
            'reason' => $this->normalizeReason($reason),
            'affected_fields' => $this->normalizeAffectedFields(
                $affectedFields,
            ),
            'metadata' => $metadata === [] ? null : $metadata,
        ];
    }

    private function assertPositiveIdentifier(
        int $identifier,
        string $field,
    ): void {
        if ($identifier < 1) {
            throw new InvalidArgumentException(
                "$field must be a positive integer.",
            );
        }
    }

    private function assertKnownStatus(
        ?string $status,
        string $field,
    ): void {
        if ($status === null) {
            return;
        }

        if (
            ! in_array(
                $status,
                DailyReport::STATUSES,
                true,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s "%s" is unknown.',
                    $field,
                    $status,
                ),
            );
        }
    }

    private function normalizeReason(
        ?string $reason,
    ): ?string {
        if ($reason === null) {
            return null;
        }

        $normalizedReason = trim($reason);

        return $normalizedReason === ''
            ? null
            : $normalizedReason;
    }

    /**
     * @param  list<string>  $affectedFields
     * @return list<string>|null
     */
    private function normalizeAffectedFields(
        array $affectedFields,
    ): ?array {
        if ($affectedFields === []) {
            return null;
        }

        $normalizedFields = [];

        foreach ($affectedFields as $field) {
            $normalizedField = trim($field);

            if ($normalizedField === '') {
                throw new InvalidArgumentException(
                    'Affected field name must not be empty.',
                );
            }

            if (
                ! in_array(
                    $normalizedField,
                    DailyReportSnapshotBuilder::SNAPSHOT_FIELDS,
                    true,
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Unknown affected daily report field "%s".',
                        $normalizedField,
                    ),
                );
            }

            if (
                in_array(
                    $normalizedField,
                    $normalizedFields,
                    true,
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Affected daily report field "%s" is duplicated.',
                        $normalizedField,
                    ),
                );
            }

            $normalizedFields[] = $normalizedField;
        }

        return $normalizedFields;
    }
}
