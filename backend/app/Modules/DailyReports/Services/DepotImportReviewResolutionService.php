<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportEvent;
use App\Modules\DailyReports\Models\DepotImportReviewResolution;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\OrganizationRelationship;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DepotImportReviewResolutionService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DepotImportIntegrityService $integrity,
    ) {}

    /** @return array<string, mixed> */
    public function correctDriver(string $batchId, string $rowId, int $driverId, string $reason, int $actorId): array
    {
        return DB::transaction(function () use ($batchId, $rowId, $driverId, $reason, $actorId): array {
            [$batch, $row] = $this->lockedContext($batchId, $rowId);
            $this->assertNoResolution($row);
            $report = $this->singleDriverRecord($batch, $row);

            if (! $report instanceof DailyReport || (int) $report->getAttribute('performed_by_driver_id') !== $driverId) {
                throw ValidationException::withMessages(['driver_id' => ['The selected driver must own the single matching daily report.']]);
            }

            $assignment = $this->eligibleAssignment($batch, $row, $driverId);
            $resolution = DepotImportReviewResolution::query()->create([
                'depot_import_batch_id' => $batch->getKey(),
                'depot_import_row_id' => $row->getKey(),
                'organization_id' => $batch->getAttribute('organization_id'),
                'resolution_type' => DepotImportReviewResolution::TYPE_DRIVER_ATTRIBUTION_CORRECTED,
                'corrected_driver_id' => $driverId,
                'corrected_driver_organization_assignment_id' => $assignment->getKey(),
                'reason' => trim($reason),
                'resolved_by_user_id' => $actorId,
            ]);
            $this->event($batch, $row, DepotImportEvent::TYPE_DRIVER_ATTRIBUTION_CORRECTED, $actorId, $reason, null, $this->payload($resolution));

            return $this->payload($resolution->load('correctedDriver'));
        });
    }

    /** @return array<string, mixed> */
    public function ignoreZeroValue(string $batchId, string $rowId, string $reason, int $actorId): array
    {
        return DB::transaction(function () use ($batchId, $rowId, $reason, $actorId): array {
            [$batch, $row] = $this->lockedContext($batchId, $rowId);
            $this->assertNoResolution($row);

            if ($this->singleDriverRecord($batch, $row) instanceof DailyReport) {
                throw ValidationException::withMessages(['row' => ['A zero-value depot record can be ignored only when no driver report exists.']]);
            }

            if (! $this->hasZeroFinancialImpact($row)) {
                throw ValidationException::withMessages(['row' => ['The depot record has non-zero operational or financial values.']]);
            }

            $resolution = DepotImportReviewResolution::query()->create([
                'depot_import_batch_id' => $batch->getKey(),
                'depot_import_row_id' => $row->getKey(),
                'organization_id' => $batch->getAttribute('organization_id'),
                'resolution_type' => DepotImportReviewResolution::TYPE_ZERO_VALUE_IGNORED,
                'corrected_driver_id' => null,
                'corrected_driver_organization_assignment_id' => null,
                'reason' => trim($reason),
                'resolved_by_user_id' => $actorId,
            ]);
            $this->event($batch, $row, DepotImportEvent::TYPE_ZERO_VALUE_IGNORED, $actorId, $reason, null, $this->payload($resolution));

            return $this->payload($resolution);
        });
    }

    /** @return array<string, mixed> */
    public function revert(string $batchId, string $rowId, string $reason, int $actorId): array
    {
        return DB::transaction(function () use ($batchId, $rowId, $reason, $actorId): array {
            [$batch, $row] = $this->lockedContext($batchId, $rowId);
            $resolution = DepotImportReviewResolution::query()->where('depot_import_row_id', $row->getKey())->lockForUpdate()->first();

            if (! $resolution instanceof DepotImportReviewResolution) {
                throw ValidationException::withMessages(['row' => ['The depot record has no active review resolution.']]);
            }

            $before = $this->payload($resolution->load('correctedDriver'));
            $resolution->delete();
            $this->event($batch, $row, DepotImportEvent::TYPE_REVIEW_RESOLUTION_REVERTED, $actorId, $reason, $before, null);

            return ['reverted' => true, 'previous_resolution' => $before];
        });
    }

    /** @return array{DepotImportBatch, DepotImportRow} */
    private function lockedContext(string $batchId, string $rowId): array
    {
        $organizationId = $this->organizationContext->requireId();
        $batch = DepotImportBatch::query()->where('organization_id', $organizationId)->where('status', DepotImportBatch::STATUS_IMPORTED)->where('public_id', $batchId)->lockForUpdate()->firstOrFail();
        $row = DepotImportRow::query()->where('depot_import_batch_id', $batch->getKey())->where('public_id', $rowId)->lockForUpdate()->firstOrFail();
        $this->integrity->assertBatchIntegrity($batch, $batch->rows()->orderBy('id')->get());

        if ($row->getAttribute('status') !== DepotImportRow::STATUS_READY) {
            throw ValidationException::withMessages(['row' => ['Only a ready depot record can be resolved.']]);
        }

        return [$batch, $row];
    }

    private function assertNoResolution(DepotImportRow $row): void
    {
        if (DepotImportReviewResolution::query()->where('depot_import_row_id', $row->getKey())->exists()) {
            throw ValidationException::withMessages(['row' => ['The depot record already has an active review resolution.']]);
        }
    }

    private function singleDriverRecord(DepotImportBatch $batch, DepotImportRow $row): ?DailyReport
    {
        $records = DailyReport::query()->forOrganization((int) $batch->getAttribute('organization_id'))
            ->whereDate('service_date', $this->date($row->getAttribute('service_date')))
            ->where('route_number_normalized', $row->getAttribute('route_number_normalized'))
            ->orderBy('id')->get();

        return $records->count() === 1 ? $records->first() : null;
    }

    private function eligibleAssignment(DepotImportBatch $batch, DepotImportRow $row, int $driverId): DriverOrganizationAssignment
    {
        $organizationId = (int) $batch->getAttribute('organization_id');
        $date = $this->date($row->getAttribute('service_date'));
        $subordinates = OrganizationRelationship::query()
            ->where('source_organization_id', $organizationId)
            ->where('relationship_type', OrganizationRelationship::TYPE_SUBCONTRACTING)
            ->where('status', OrganizationRelationship::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
            ->pluck('target_organization_id')->map(static fn (mixed $id): int => (int) $id)->all();

        $assignment = DriverOrganizationAssignment::query()
            ->where('driver_id', $driverId)
            ->whereIn('organization_id', array_values(array_unique([$organizationId, ...$subordinates])))
            ->whereDate('valid_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
            ->whereHas('driver', fn ($query) => $query->where('status', Driver::STATUS_ACTIVE)->where('active', true))
            ->orderByDesc('valid_from')->first();

        if (! $assignment instanceof DriverOrganizationAssignment) {
            throw ValidationException::withMessages(['driver_id' => ['The selected driver is not eligible for this depot record.']]);
        }

        return $assignment;
    }

    private function hasZeroFinancialImpact(DepotImportRow $row): bool
    {
        foreach (['loaded_parcels', 'delivered_parcels', 'redirected_parcels', 'customer_rejected_parcels', 'computed_not_delivered_parcels'] as $field) {
            if ((int) ($row->getAttribute($field) ?? 0) !== 0) {
                return false;
            }
        }

        foreach (['actual_km', 'planned_km', 'surcharge_amount'] as $field) {
            if (abs((float) ($row->getAttribute($field) ?? 0)) > 0.00001) {
                return false;
            }
        }

        return $row->getAttribute('departure_time') === null && $row->getAttribute('arrival_time') === null;
    }

    private function event(DepotImportBatch $batch, DepotImportRow $row, string $type, int $actorId, string $reason, ?array $before, ?array $after): void
    {
        DepotImportEvent::query()->create([
            'depot_import_batch_id' => $batch->getKey(), 'depot_import_row_id' => $row->getKey(),
            'organization_id' => $batch->getAttribute('organization_id'), 'event_type' => $type,
            'acted_by_user_id' => $actorId, 'reason' => trim($reason),
            'before_payload' => $before, 'after_payload' => $after,
            'protected_totals_sha256_before' => $batch->getAttribute('protected_totals_sha256'),
            'protected_totals_sha256_after' => $batch->getAttribute('protected_totals_sha256'),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(DepotImportReviewResolution $resolution): array
    {
        $driver = $resolution->correctedDriver;

        return [
            'type' => (string) $resolution->getAttribute('resolution_type'),
            'corrected_driver' => $driver instanceof Driver ? ['id' => (int) $driver->getKey(), 'name' => $driver->full_name] : null,
            'reason' => (string) $resolution->getAttribute('reason'),
            'resolved_by_user_id' => (int) $resolution->getAttribute('resolved_by_user_id'),
            'created_at' => optional($resolution->getAttribute('created_at'))->toISOString(),
        ];
    }

    private function date(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
