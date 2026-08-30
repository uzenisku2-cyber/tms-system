<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionDriverAttribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelTransactionDriverAttributionService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function show(FuelTransaction $transaction, int $organizationId): array
    {
        $this->assertVisible($transaction, $organizationId);
        $transaction->load(['importedDriver:id,first_name,last_name', 'actualDriver:id,first_name,last_name', 'driverAttributions.previousDriver:id,first_name,last_name', 'driverAttributions.newDriver:id,first_name,last_name', 'driverAttributions.correctedBy:id,name']);

        return $this->payload($transaction);
    }

    public function eligibleDrivers(FuelTransaction $transaction, int $organizationId, User $actor): array
    {
        $this->assertVisible($transaction, $organizationId);
        $ids = $this->authorization->visibleDriverOrganizationAssignmentIds($actor, $organizationId, DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION);
        $date = $transaction->occurred_at->toDateString();

        return DriverOrganizationAssignment::query()->with('driver:id,first_name,last_name,status,active')->whereIn('id', $ids)
            ->whereDate('valid_from', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
            ->whereHas('driver', fn (Builder $query) => $query->where('status', Driver::STATUS_ACTIVE)->where('active', true))
            ->orderBy('driver_id')->get()->map(fn (DriverOrganizationAssignment $assignment): array => [
                'driver_id' => (int) $assignment->driver_id,
                'driver_name' => $assignment->driver?->full_name,
                'organization_id' => (int) $assignment->organization_id,
                'driver_organization_assignment_id' => (int) $assignment->getKey(),
            ])->values()->all();
    }

    public function correct(FuelTransaction $transaction, int $organizationId, User $actor, int $driverId, int $expectedRevision, string $reason): array
    {
        $this->assertVisible($transaction, $organizationId);
        $this->authorization->findVisibleDriver($actor, $organizationId, $driverId);

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $driverId, $expectedRevision, $reason): array {
            $locked = FuelTransaction::query()->whereKey($transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $currentRevision = (int) $locked->driver_attribution_revision;
            if ($currentRevision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The fuel transaction driver attribution revision is stale.']]);
            }
            $date = $locked->occurred_at->toDateString();
            $assignment = DriverOrganizationAssignment::query()->where('driver_id', $driverId)->whereDate('valid_from', '<=', $date)
                ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))
                ->orderByDesc('valid_from')->first();
            if (! $assignment instanceof DriverOrganizationAssignment) {
                throw ValidationException::withMessages(['driver_id' => ['The selected driver has no organization assignment valid at the transaction time.']]);
            }
            $this->authorization->findManageableOrganization($actor, $organizationId, (int) $assignment->organization_id);
            $previousDriverId = $locked->effectiveDriverId();
            if ($previousDriverId === $driverId) {
                throw ValidationException::withMessages(['driver_id' => ['The selected driver is already the effective fueling driver.']]);
            }
            $previousAssignmentId = $locked->actual_driver_organization_assignment_id;
            if ($previousAssignmentId === null && $previousDriverId !== null) {
                $previousAssignmentId = DriverOrganizationAssignment::query()->where('driver_id', $previousDriverId)->whereDate('valid_from', '<=', $date)
                    ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))->value('id');
            }
            $nextRevision = $currentRevision + 1;
            FuelTransactionDriverAttribution::query()->create([
                'public_id' => (string) Str::uuid(), 'fuel_transaction_id' => $locked->getKey(), 'revision' => $nextRevision,
                'previous_driver_id' => $previousDriverId, 'new_driver_id' => $driverId,
                'previous_driver_organization_assignment_id' => $previousAssignmentId,
                'new_driver_organization_assignment_id' => $assignment->getKey(), 'reason' => trim($reason),
                'corrected_by_user_id' => $actor->getKey(), 'corrected_at' => now(),
            ]);
            $locked->forceFill(['actual_driver_id' => $driverId, 'actual_driver_organization_assignment_id' => $assignment->getKey(), 'driver_attribution_revision' => $nextRevision])->save();

            return $this->show($locked->refresh(), $organizationId);
        });
    }

    private function assertVisible(FuelTransaction $transaction, int $organizationId): void
    {
        abort_unless((int) $transaction->owner_organization_id === $organizationId, 404);
    }

    private function payload(FuelTransaction $transaction): array
    {
        return [
            'transaction_public_id' => $transaction->public_id,
            'imported_driver_id' => $transaction->driver_id === null ? null : (int) $transaction->driver_id,
            'imported_driver' => $transaction->importedDriver === null ? null : ['id' => (int) $transaction->importedDriver->getKey(), 'name' => $transaction->importedDriver->full_name],
            'actual_driver_id' => $transaction->actual_driver_id === null ? null : (int) $transaction->actual_driver_id,
            'actual_driver' => $transaction->actualDriver === null ? null : ['id' => (int) $transaction->actualDriver->getKey(), 'name' => $transaction->actualDriver->full_name],
            'effective_driver_id' => $transaction->effectiveDriverId(),
            'revision' => (int) $transaction->driver_attribution_revision,
            'history' => $transaction->driverAttributions->map(fn (FuelTransactionDriverAttribution $event): array => [
                'public_id' => $event->public_id, 'revision' => (int) $event->revision,
                'previous_driver_id' => $event->previous_driver_id === null ? null : (int) $event->previous_driver_id,
                'previous_driver_name' => $event->previousDriver?->full_name,
                'new_driver_id' => (int) $event->new_driver_id, 'new_driver_name' => $event->newDriver?->full_name,
                'reason' => $event->reason,
                'corrected_by' => $event->correctedBy?->only(['id', 'name']), 'corrected_at' => $event->corrected_at?->toISOString(),
            ])->values()->all(),
        ];
    }
}
