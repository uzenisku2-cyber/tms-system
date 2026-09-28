<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleFinancingAgreement;
use App\Modules\Fleet\Models\VehicleInstallmentSchedule;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleInstallmentScheduleAdministrationService
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
            $this->assertRevision($vehicle, (int) $data['expected_revision']);
            $financing = $this->lockLatestFinancing($vehicle, $data, $organizationId);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleInstallmentSchedule::query()->create($this->attributes($financing, $data, $actor) + ['revision' => 1]);
            $this->advanceAndAudit($vehicle, $record, $financing, $document, $organizationId, $actor, 'vehicle_installment_schedule_registered', (string) $data['reason']);

            return $this->result($vehicle, $record, $financing, $document);
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
            $this->assertRevision($vehicle, (int) $data['expected_revision']);
            $financing = $this->lockLatestFinancing($vehicle, $data, $organizationId);
            $previous = VehicleInstallmentSchedule::query()->where('public_id', $recordPublicId)
                ->whereHas('financingAgreement', fn (Builder $query) => $query->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId))
                ->lockForUpdate()->first();
            if (! $previous instanceof VehicleInstallmentSchedule) {
                throw (new ModelNotFoundException)->setModel(VehicleInstallmentSchedule::class, [$recordPublicId]);
            }
            $latest = VehicleInstallmentSchedule::query()->where('schedule_uid', $previous->schedule_uid)
                ->whereHas('financingAgreement', fn (Builder $query) => $query->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId))
                ->orderByDesc('revision')->lockForUpdate()->first();
            if (! $latest instanceof VehicleInstallmentSchedule
                || (int) $latest->revision !== (int) $data['expected_schedule_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle installment schedule revision is stale.');
            }
            if ((int) $latest->vehicle_financing_agreement_id !== (int) $financing->id) {
                throw new ConflictHttpException('Financing agreement has changed; register a new schedule.');
            }
            if (VehicleInstallmentSchedule::query()->where('schedule_uid', $latest->schedule_uid)
                ->whereHas('installments')->exists()) {
                throw new ConflictHttpException('A schedule with recorded installments cannot be corrected.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleInstallmentSchedule::query()->create($this->attributes($financing, $data, $actor) + [
                'schedule_uid' => $latest->schedule_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $financing, $document, $organizationId, $actor, 'vehicle_installment_schedule_revised', (string) $data['reason']);

            return $this->result($vehicle, $record, $financing, $document);
        });
    }

    private function lockVisibleVehicle(string $publicId, int $organizationId): Vehicle
    {
        $vehicle = Vehicle::query()->where('public_id', $publicId)
            ->where(static function (Builder $query) use ($organizationId): void {
                $query->whereHas('ownerships', fn (Builder $relation) => $relation->where('organization_context_id', $organizationId))
                    ->orWhereHas('responsibilities', fn (Builder $relation) => $relation->where('organization_context_id', $organizationId));
            })->lockForUpdate()->first();
        if (! $vehicle instanceof Vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
        }

        return $vehicle;
    }

    /** @param array<string, mixed> $data */
    private function lockLatestFinancing(Vehicle $vehicle, array $data, int $organizationId): VehicleFinancingAgreement
    {
        $publicId = (string) $data['financing_public_id'];
        $financing = VehicleFinancingAgreement::query()->where('public_id', $publicId)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->lockForUpdate()->first();
        if (! $financing instanceof VehicleFinancingAgreement) {
            throw (new ModelNotFoundException)->setModel(VehicleFinancingAgreement::class, [$publicId]);
        }
        $latest = VehicleFinancingAgreement::query()->where('financing_uid', $financing->financing_uid)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->orderByDesc('revision')->lockForUpdate()->first();
        if (! $latest instanceof VehicleFinancingAgreement
            || $latest->public_id !== $publicId
            || (int) $latest->revision !== (int) $data['expected_financing_revision']) {
            throw new ConflictHttpException('Vehicle financing agreement revision is stale.');
        }

        return $latest;
    }

    private function lockVerifiedDocument(Vehicle $vehicle, string $publicId, int $organizationId): VehicleDocument
    {
        $document = VehicleDocument::query()->where('public_id', $publicId)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->where('verification_status', 'verified')->lockForUpdate()->first();
        if (! $document instanceof VehicleDocument) {
            throw (new ModelNotFoundException)->setModel(VehicleDocument::class, [$publicId]);
        }

        return $document;
    }

    private function assertRevision(Vehicle $vehicle, int $expectedRevision): void
    {
        if ((int) $vehicle->current_revision !== $expectedRevision) {
            throw new ConflictHttpException('Vehicle revision is stale.');
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function attributes(VehicleFinancingAgreement $financing, array $data, User $actor): array
    {
        abort_if($data['currency'] !== $financing->currency, 422, 'Schedule currency must match financing currency.');
        $startsOn = substr((string) $data['starts_on'], 0, 10);
        $effectiveFrom = $financing->effective_from->toDateString();
        abort_if($startsOn < $effectiveFrom, 422, 'Schedule starts before financing agreement.');
        if (isset($data['ends_on']) && $financing->effective_until !== null) {
            abort_if(substr((string) $data['ends_on'], 0, 10) > $financing->effective_until->toDateString(), 422, 'Schedule ends after financing agreement.');
        }

        return [
            'vehicle_financing_agreement_id' => $financing->id,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'installment_count' => $data['installment_count'],
            'planned_total_amount' => $data['planned_total_amount'],
            'currency' => $data['currency'],
            'frequency' => $data['frequency'],
            'status' => $data['status'],
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleInstallmentSchedule $record, VehicleFinancingAgreement $financing, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'schedule_public_id' => $record->public_id,
                'schedule_uid' => $record->schedule_uid,
                'schedule_revision' => (int) $record->revision,
                'financing_public_id' => $financing->public_id,
                'financing_uid' => $financing->financing_uid,
                'financing_revision' => (int) $financing->revision,
                'status' => $record->status,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleInstallmentSchedule $record, VehicleFinancingAgreement $financing, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'installment_schedule' => [
                'public_id' => $record->public_id,
                'schedule_uid' => $record->schedule_uid,
                'revision' => (int) $record->revision,
                'status' => $record->status,
            ],
            'financing_agreement' => [
                'public_id' => $financing->public_id,
                'financing_uid' => $financing->financing_uid,
                'revision' => (int) $financing->revision,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
