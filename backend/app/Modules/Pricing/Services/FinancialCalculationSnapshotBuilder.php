<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use DateTimeInterface;
use DomainException;
use InvalidArgumentException;
use LogicException;

final class FinancialCalculationSnapshotBuilder
{
    /**
     * Deterministic output order for serialized financial snapshots.
     *
     * @var list<string>
     */
    public const SNAPSHOT_FIELDS = [
        'daily_report_id',
        'daily_report_version',
        'public_id',
        'organization_id',
        'trip_id',
        'performed_by_driver_id',
        'vehicle_id',
        'route_number',
        'route_number_normalized',
        'service_date',
        'status',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'customer_rejected_parcels',
        'not_delivered_parcels',
        'processed_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'approved_at',
        'approved_by_user_id',
        'closed_at',
        'captured_at',
    ];

    /**
     * @return array{
     *     daily_report_id: int,
     *     daily_report_version: int,
     *     public_id: string,
     *     organization_id: int,
     *     trip_id: int|null,
     *     performed_by_driver_id: int,
     *     vehicle_id: int|null,
     *     route_number: string,
     *     route_number_normalized: string,
     *     service_date: string,
     *     status: string,
     *     loaded_parcels: int,
     *     delivered_parcels: int,
     *     redirected_parcels: int,
     *     undelivered_parcels: int,
     *     customer_rejected_parcels: int,
     *     not_delivered_parcels: int,
     *     processed_parcels: int,
     *     planned_km: string|null,
     *     actual_km: string,
     *     actual_km_source: string|null,
     *     approved_at: string,
     *     approved_by_user_id: int,
     *     closed_at: string|null,
     *     captured_at: string
     * }
     */
    public function build(
        DailyReportVersion $dailyReportVersion,
        DateTimeInterface $capturedAt,
    ): array {
        $sourceSnapshot =
            $dailyReportVersion->getAttribute('snapshot');

        if (! is_array($sourceSnapshot)) {
            throw new LogicException(
                'The daily-report version does not contain a valid snapshot.',
            );
        }

        $dailyReportId = $this->positiveInteger(
            $dailyReportVersion->getAttribute(
                'daily_report_id',
            ),
            'Daily-report identifier',
        );

        $versionNumber = $this->positiveInteger(
            $dailyReportVersion->getAttribute(
                'version_number',
            ),
            'Daily-report version number',
        );

        $snapshotVersion = $this->positiveInteger(
            $this->sourceValue(
                $sourceSnapshot,
                'current_version',
            ),
            'Snapshot current version',
        );

        if ($snapshotVersion !== $versionNumber) {
            throw new DomainException(
                sprintf(
                    (
                        'Daily-report snapshot version conflict: '.
                        'stored version %d, snapshot version %d.'
                    ),
                    $versionNumber,
                    $snapshotVersion,
                ),
            );
        }

        $status = $this->requiredString(
            $this->sourceValue(
                $sourceSnapshot,
                'status',
            ),
            'Daily-report snapshot status',
        );

        if (
            ! in_array(
                $status,
                [
                    DailyReport::STATUS_APPROVED,
                    DailyReport::STATUS_CLOSED,
                ],
                true,
            )
        ) {
            throw new DomainException(
                sprintf(
                    (
                        'Financial calculation requires an approved '.
                        'or closed daily-report snapshot; status [%s] given.'
                    ),
                    $status,
                ),
            );
        }

        $approvedAt = $this->requiredString(
            $this->sourceValue(
                $sourceSnapshot,
                'approved_at',
            ),
            'Daily-report approval time',
        );

        $approvedByUserId = $this->positiveInteger(
            $this->sourceValue(
                $sourceSnapshot,
                'approved_by_user_id',
            ),
            'Approving user identifier',
        );

        $closedAt = $this->nullableString(
            $this->sourceValue(
                $sourceSnapshot,
                'closed_at',
            ),
            'Daily-report closure time',
        );

        if (
            $status === DailyReport::STATUS_CLOSED
            && $closedAt === null
        ) {
            throw new DomainException(
                'A closed daily-report snapshot must contain a closure time.',
            );
        }

        if (
            $status === DailyReport::STATUS_APPROVED
            && $closedAt !== null
        ) {
            throw new DomainException(
                'An approved daily-report snapshot cannot contain a closure time.',
            );
        }

        $parcelMetrics =
            (new FinancialSnapshotParcelMetricResolver)->resolve(
                $sourceSnapshot,
            );

        $snapshot = [
            'daily_report_id' => $dailyReportId,
            'daily_report_version' => $versionNumber,

            'public_id' => $this->requiredString(
                $this->sourceValue(
                    $sourceSnapshot,
                    'public_id',
                ),
                'Daily-report public identifier',
            ),

            'organization_id' => $this->positiveInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'organization_id',
                ),
                'Daily-report organization identifier',
            ),

            'trip_id' => $this->nullablePositiveInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'trip_id',
                ),
                'Trip identifier',
            ),

            'performed_by_driver_id' => $this->positiveInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'performed_by_driver_id',
                ),
                'Performing driver identifier',
            ),

            'vehicle_id' => $this->nullablePositiveInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'vehicle_id',
                ),
                'Vehicle identifier',
            ),

            'route_number' => $this->requiredString(
                $this->sourceValue(
                    $sourceSnapshot,
                    'route_number',
                ),
                'Route number',
            ),

            'route_number_normalized' => $this->requiredString(
                $this->sourceValue(
                    $sourceSnapshot,
                    'route_number_normalized',
                ),
                'Normalized route number',
            ),

            'service_date' => $this->requiredString(
                $this->sourceValue(
                    $sourceSnapshot,
                    'service_date',
                ),
                'Service date',
            ),

            'status' => $status,

            'loaded_parcels' => $parcelMetrics['loaded_parcels'],

            'delivered_parcels' => $this->nonNegativeInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'delivered_parcels',
                ),
                'Delivered parcel count',
            ),

            'redirected_parcels' => $this->nonNegativeInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'redirected_parcels',
                ),
                'Redirected parcel count',
            ),

            'undelivered_parcels' => $this->nonNegativeInteger(
                $this->sourceValue(
                    $sourceSnapshot,
                    'undelivered_parcels',
                ),
                'Undelivered parcel count',
            ),

            'customer_rejected_parcels' => $parcelMetrics['customer_rejected_parcels'],

            'not_delivered_parcels' => $parcelMetrics['not_delivered_parcels'],

            'processed_parcels' => $parcelMetrics['processed_parcels'],

            'planned_km' => $this->nullableDecimal(
                $this->sourceValue(
                    $sourceSnapshot,
                    'planned_km',
                ),
                'Planned kilometres',
            ),

            'actual_km' => $this->requiredDecimal(
                $this->sourceValue(
                    $sourceSnapshot,
                    'actual_km',
                ),
                'Actual kilometres',
            ),

            'actual_km_source' => $this->nullableString(
                $this->sourceValue(
                    $sourceSnapshot,
                    'actual_km_source',
                ),
                'Actual-kilometre source',
            ),

            'approved_at' => $approvedAt,
            'approved_by_user_id' => $approvedByUserId,
            'closed_at' => $closedAt,

            'captured_at' => $capturedAt->format('Y-m-d H:i:s'),
        ];

        if (
            array_keys($snapshot)
            !== self::SNAPSHOT_FIELDS
        ) {
            throw new LogicException(
                'The financial snapshot field order is inconsistent.',
            );
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function sourceValue(
        array $snapshot,
        string $field,
    ): mixed {
        if (! array_key_exists($field, $snapshot)) {
            throw new InvalidArgumentException(
                sprintf(
                    (
                        'Daily-report version snapshot '.
                        'field [%s] is missing.'
                    ),
                    $field,
                ),
            );
        }

        return $snapshot[$field];
    }

    private function requiredString(
        mixed $value,
        string $label,
    ): string {
        if (
            ! is_string($value)
            || trim($value) === ''
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-empty string.',
                    $label,
                ),
            );
        }

        return $value;
    }

    private function nullableString(
        mixed $value,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        return $this->requiredString(
            $value,
            $label,
        );
    }

    private function positiveInteger(
        mixed $value,
        string $label,
    ): int {
        $integer = $this->nonNegativeInteger(
            $value,
            $label,
        );

        if ($integer < 1) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be at least 1.',
                    $label,
                ),
            );
        }

        return $integer;
    }

    private function nullablePositiveInteger(
        mixed $value,
        string $label,
    ): ?int {
        if ($value === null) {
            return null;
        }

        return $this->positiveInteger(
            $value,
            $label,
        );
    }

    private function nonNegativeInteger(
        mixed $value,
        string $label,
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match(
                    '/^(?:0|[1-9][0-9]*)$/D',
                    $value,
                ) === 1
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative integer.',
                    $label,
                ),
            );
        }

        $integer = (int) $value;

        if ($integer < 0) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative integer.',
                    $label,
                ),
            );
        }

        return $integer;
    }

    private function requiredDecimal(
        mixed $value,
        string $label,
    ): string {
        $decimal = $this->nullableDecimal(
            $value,
            $label,
        );

        if ($decimal === null) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must not be null.',
                    $label,
                ),
            );
        }

        return $decimal;
    }

    private function nullableDecimal(
        mixed $value,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            $value = (string) $value;
        }

        if (
            ! is_string($value)
            || preg_match(
                '/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    (
                        '%s must be a non-negative '.
                        'decimal string or integer.'
                    ),
                    $label,
                ),
            );
        }

        return $value;
    }
}
