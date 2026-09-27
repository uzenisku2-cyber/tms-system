<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Fleet\Models\VehicleServiceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleServiceEvidenceService
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
            $record = VehicleServiceRecord::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'revision' => 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_service_evidence_registered', (string) $data['reason']);

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
            $previous = VehicleServiceRecord::query()
                ->where('public_id', $recordPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $previous instanceof VehicleServiceRecord) {
                throw (new ModelNotFoundException)->setModel(VehicleServiceRecord::class, [$recordPublicId]);
            }
            $latest = VehicleServiceRecord::query()
                ->where('record_uid', $previous->record_uid)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->orderByDesc('revision')
                ->lockForUpdate()
                ->first();
            if (! $latest instanceof VehicleServiceRecord
                || (int) $latest->revision !== (int) $data['expected_service_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle service evidence revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleServiceRecord::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'record_uid' => $latest->record_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_service_evidence_revised', (string) $data['reason']);

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
        $providerId = $data['provider_organization_id'] ?? null;
        abort_if($providerId !== null && (int) $providerId !== $organizationId, 422, 'Provider organization must match the active organization.');

        return [
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'service_type' => $data['service_type'],
            'status' => $data['status'],
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'opened_at' => $data['opened_at'],
            'completed_at' => $data['completed_at'] ?? null,
            'next_service_on' => $data['next_service_on'] ?? null,
            'odometer' => $data['odometer'] ?? null,
            'next_service_odometer' => $data['next_service_odometer'] ?? null,
            'provider_organization_id' => $providerId,
            'external_provider_name' => $data['external_provider_name'] ?? null,
            'primary_document_id' => $document->id,
            'recorded_by_user_id' => $actor->id,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleServiceRecord $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'service_public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'service_revision' => (int) $record->revision,
                'service_type' => $record->service_type,
                'status' => $record->status,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleServiceRecord $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'service_record' => [
                'public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'revision' => (int) $record->revision,
                'service_type' => $record->service_type,
                'status' => $record->status,
                'summary' => $record->summary,
                'details' => $record->details,
                'opened_at' => $record->opened_at?->toIso8601String(),
                'completed_at' => $record->completed_at?->toIso8601String(),
                'next_service_on' => $record->next_service_on?->toDateString(),
                'odometer' => $record->odometer,
                'next_service_odometer' => $record->next_service_odometer,
                'provider_organization_id' => $record->provider_organization_id,
                'external_provider_name' => $record->external_provider_name,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
