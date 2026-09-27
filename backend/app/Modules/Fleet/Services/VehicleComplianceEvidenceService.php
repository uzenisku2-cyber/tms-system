<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleComplianceRecord;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleComplianceEvidenceService
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
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleComplianceRecord::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'revision' => 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_compliance_evidence_registered', (string) $data['reason']);

            return $this->result($vehicle, $record, $document);
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
            $previous = VehicleComplianceRecord::query()
                ->where('public_id', $recordPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $previous instanceof VehicleComplianceRecord) {
                throw (new ModelNotFoundException)->setModel(VehicleComplianceRecord::class, [$recordPublicId]);
            }
            $latest = VehicleComplianceRecord::query()
                ->where('record_uid', $previous->record_uid)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->orderByDesc('revision')
                ->lockForUpdate()
                ->first();
            if (! $latest instanceof VehicleComplianceRecord
                || (int) $latest->revision !== (int) $data['expected_compliance_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle compliance evidence revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleComplianceRecord::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'record_uid' => $latest->record_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_compliance_evidence_revised', (string) $data['reason']);

            return $this->result($vehicle, $record, $document);
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
    private function attributes(Vehicle $vehicle, VehicleDocument $document, array $data, int $organizationId, User $actor): array
    {
        return [
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'compliance_type' => $data['compliance_type'],
            'identifier' => $data['identifier'] ?? null,
            'inspected_at' => $data['inspected_at'] ?? null,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?? null,
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'odometer' => $data['odometer'] ?? null,
            'issuer_name' => $data['issuer_name'] ?? null,
            'primary_document_id' => $document->id,
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleComplianceRecord $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'compliance_public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'compliance_revision' => (int) $record->revision,
                'compliance_type' => $record->compliance_type,
                'status' => $record->status,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleComplianceRecord $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'compliance_record' => [
                'public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'revision' => (int) $record->revision,
                'compliance_type' => $record->compliance_type,
                'identifier' => $record->identifier,
                'inspected_at' => $record->inspected_at?->toDateString(),
                'valid_from' => $record->valid_from?->toDateString(),
                'valid_until' => $record->valid_until?->toDateString(),
                'status' => $record->status,
                'result' => $record->result,
                'odometer' => $record->odometer,
                'issuer_name' => $record->issuer_name,
                'notes' => $record->notes,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
