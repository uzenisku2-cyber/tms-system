<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleFinancingAgreement;
use App\Modules\Fleet\Models\VehicleInstallment;
use App\Modules\Fleet\Models\VehicleInstallmentSchedule;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleInstallmentAdministrationService
{
    public function __construct(private readonly VehicleRegistryAdministrationReadService $readService) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function store(string $vehiclePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);
        $this->readService->show($vehiclePublicId, $organizationId, $actor);

        return DB::transaction(function () use ($vehiclePublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, $data);
            $schedule = $this->lockLatestSchedule($vehicle, $data, $organizationId);
            $this->assertSequenceAvailable($schedule, (int) $data['sequence_number']);
            $document = $this->lockVerifiedDocument($vehicle, $data, $organizationId);
            $record = VehicleInstallment::query()->create($this->attributes($schedule, $data, $actor) + ['revision' => 1]);
            $this->advanceAndAudit($vehicle, $schedule, $record, $document, $organizationId, $actor, 'vehicle_installment_registered', (string) $data['reason']);

            return $this->result($vehicle, $schedule, $record, $document);
        });
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function revise(string $vehiclePublicId, string $recordPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);
        $this->readService->show($vehiclePublicId, $organizationId, $actor);

        return DB::transaction(function () use ($vehiclePublicId, $recordPublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, $data);
            $schedule = $this->lockLatestSchedule($vehicle, $data, $organizationId);
            $previous = VehicleInstallment::query()->where('public_id', $recordPublicId)
                ->whereHas('schedule.financingAgreement', fn (Builder $q) => $q->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId))
                ->lockForUpdate()->first();
            if (! $previous instanceof VehicleInstallment) {
                throw (new ModelNotFoundException)->setModel(VehicleInstallment::class, [$recordPublicId]);
            }
            $latest = VehicleInstallment::query()->where('installment_uid', $previous->installment_uid)
                ->orderByDesc('revision')->lockForUpdate()->first();
            if (! $latest instanceof VehicleInstallment || $latest->public_id !== $recordPublicId
                || (int) $latest->revision !== (int) $data['expected_installment_revision']) {
                throw new ConflictHttpException('Vehicle installment revision is stale.');
            }
            if ((int) $latest->vehicle_installment_schedule_id !== (int) $schedule->id) {
                throw new ConflictHttpException('Schedule has changed; register a new installment.');
            }
            if ((int) $latest->sequence_number !== (int) $data['sequence_number']) {
                abort(422, 'Installment sequence cannot be changed.');
            }
            $document = $this->lockVerifiedDocument($vehicle, $data, $organizationId);
            $record = VehicleInstallment::query()->create($this->attributes($schedule, $data, $actor) + [
                'installment_uid' => $latest->installment_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $schedule, $record, $document, $organizationId, $actor, 'vehicle_installment_revised', (string) $data['reason']);

            return $this->result($vehicle, $schedule, $record, $document);
        });
    }

    private function lockVisibleVehicle(string $publicId, int $organizationId): Vehicle
    {
        $vehicle = Vehicle::query()->where('public_id', $publicId)->where(static function (Builder $q) use ($organizationId): void {
            $q->whereHas('ownerships', fn (Builder $r) => $r->where('organization_context_id', $organizationId))
                ->orWhereHas('responsibilities', fn (Builder $r) => $r->where('organization_context_id', $organizationId));
        })->lockForUpdate()->first();
        if (! $vehicle instanceof Vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
        }

        return $vehicle;
    }

    /** @param array<string, mixed> $data */
    private function assertVehicleRevision(Vehicle $vehicle, array $data): void
    {
        if ((int) $vehicle->current_revision !== (int) $data['expected_revision']) {
            throw new ConflictHttpException('Vehicle revision is stale.');
        }
    }

    /** @param array<string, mixed> $data */
    private function lockLatestSchedule(Vehicle $vehicle, array $data, int $organizationId): VehicleInstallmentSchedule
    {
        $publicId = (string) $data['schedule_public_id'];
        $schedule = VehicleInstallmentSchedule::query()->where('public_id', $publicId)
            ->whereHas('financingAgreement', fn (Builder $q) => $q->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId))
            ->lockForUpdate()->first();
        if (! $schedule instanceof VehicleInstallmentSchedule) {
            throw (new ModelNotFoundException)->setModel(VehicleInstallmentSchedule::class, [$publicId]);
        }
        $latest = VehicleInstallmentSchedule::query()->where('schedule_uid', $schedule->schedule_uid)
            ->orderByDesc('revision')->lockForUpdate()->first();
        if (! $latest instanceof VehicleInstallmentSchedule || $latest->public_id !== $publicId
            || (int) $latest->revision !== (int) $data['expected_schedule_revision']) {
            throw new ConflictHttpException('Vehicle installment schedule revision is stale.');
        }
        $financing = $schedule->financingAgreement;
        $latestFinancing = VehicleFinancingAgreement::query()->where('financing_uid', $financing->financing_uid)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->orderByDesc('revision')->lockForUpdate()->first();
        if (! $latestFinancing instanceof VehicleFinancingAgreement || (int) $latestFinancing->id !== (int) $financing->id) {
            throw new ConflictHttpException('Financing agreement has changed; register a new schedule.');
        }
        abort_unless(in_array($schedule->status, ['draft', 'active'], true), 422, 'Schedule is not open for installments.');

        return $schedule;
    }

    private function assertSequenceAvailable(VehicleInstallmentSchedule $schedule, int $sequence): void
    {
        abort_if($sequence > (int) $schedule->installment_count, 422, 'Installment sequence exceeds schedule count.');
        if (VehicleInstallment::query()->where('vehicle_installment_schedule_id', $schedule->id)
            ->where('sequence_number', $sequence)->exists()) {
            throw new ConflictHttpException('Installment sequence already exists.');
        }
    }

    /** @param array<string, mixed> $data */
    private function lockVerifiedDocument(Vehicle $vehicle, array $data, int $organizationId): VehicleDocument
    {
        $publicId = (string) $data['source_document_public_id'];
        $document = VehicleDocument::query()->where('public_id', $publicId)->where('vehicle_id', $vehicle->id)
            ->where('organization_context_id', $organizationId)->where('verification_status', 'verified')->lockForUpdate()->first();
        if (! $document instanceof VehicleDocument) {
            throw (new ModelNotFoundException)->setModel(VehicleDocument::class, [$publicId]);
        }

        return $document;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function attributes(VehicleInstallmentSchedule $schedule, array $data, User $actor): array
    {
        abort_if($data['currency'] !== $schedule->currency, 422, 'Installment currency must match schedule.');
        $dueOn = substr((string) $data['due_on'], 0, 10);
        abort_if($dueOn < $schedule->starts_on->toDateString()
            || ($schedule->ends_on !== null && $dueOn > $schedule->ends_on->toDateString()), 422, 'Installment date is outside schedule.');
        abort_if((int) $data['sequence_number'] > (int) $schedule->installment_count, 422, 'Installment sequence exceeds schedule count.');
        $cents = static fn (mixed $amount): int => (int) round((float) $amount * 100);
        abort_if($cents($data['total_amount']) !== $cents($data['principal_amount']) + $cents($data['finance_charge_amount']) + $cents($data['other_amount']), 422, 'Installment total differs from components.');

        return [
            'vehicle_installment_schedule_id' => $schedule->id,
            'sequence_number' => $data['sequence_number'],
            'due_on' => $data['due_on'],
            'principal_amount' => $data['principal_amount'],
            'finance_charge_amount' => $data['finance_charge_amount'],
            'other_amount' => $data['other_amount'],
            'total_amount' => $data['total_amount'],
            'currency' => $data['currency'],
            'status' => $data['status'],
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleInstallmentSchedule $schedule, VehicleInstallment $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
    {
        $revision = (int) $vehicle->current_revision + 1;
        $vehicle->update(['current_revision' => $revision]);
        VehicleRegistryEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'actor_user_id' => $actor->id,
            'event_type' => $type,
            'vehicle_revision' => $revision,
            'reason' => $reason,
            'payload' => [
                'installment_public_id' => $record->public_id,
                'installment_uid' => $record->installment_uid,
                'installment_revision' => (int) $record->revision,
                'schedule_public_id' => $schedule->public_id,
                'schedule_uid' => $schedule->schedule_uid,
                'schedule_revision' => (int) $schedule->revision,
                'sequence_number' => (int) $record->sequence_number,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleInstallmentSchedule $schedule, VehicleInstallment $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'installment' => ['public_id' => $record->public_id, 'installment_uid' => $record->installment_uid, 'revision' => (int) $record->revision, 'sequence_number' => (int) $record->sequence_number, 'status' => $record->status],
            'installment_schedule' => ['public_id' => $schedule->public_id, 'schedule_uid' => $schedule->schedule_uid, 'revision' => (int) $schedule->revision],
            'source_document' => ['public_id' => $document->public_id, 'verification_status' => $document->verification_status, 'revision' => (int) $document->revision],
        ];
    }
}
