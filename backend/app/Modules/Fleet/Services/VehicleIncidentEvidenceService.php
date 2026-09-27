<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleIncident;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleIncidentEvidenceService
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
            $record = VehicleIncident::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'revision' => 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_incident_evidence_registered', (string) $data['reason']);

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
            $previous = VehicleIncident::query()
                ->where('public_id', $recordPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $previous instanceof VehicleIncident) {
                throw (new ModelNotFoundException)->setModel(VehicleIncident::class, [$recordPublicId]);
            }
            $latest = VehicleIncident::query()
                ->where('record_uid', $previous->record_uid)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->orderByDesc('revision')
                ->lockForUpdate()
                ->first();
            if (! $latest instanceof VehicleIncident
                || (int) $latest->revision !== (int) $data['expected_incident_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle incident evidence revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleIncident::query()->create($this->attributes($vehicle, $document, $data, $organizationId, $actor) + [
                'record_uid' => $latest->record_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_incident_evidence_revised', (string) $data['reason']);

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
        $responsibleId = $data['responsible_organization_id'] ?? null;
        abort_if($responsibleId !== null && (int) $responsibleId !== $organizationId, 422, 'Responsible organization must match the active organization.');
        $driverId = $data['driver_user_id'] ?? null;
        if ($driverId !== null) {
            $isMember = OrganizationMembership::query()
                ->where('organization_id', $organizationId)
                ->where('user_id', (int) $driverId)
                ->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->where('valid_from', '<=', now())
                ->where(static fn (Builder $query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
                ->exists();
            abort_unless($isMember, 422, 'Driver must be an active member of the organization.');
        }

        return [
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'incident_type' => $data['incident_type'],
            'occurred_at' => $data['occurred_at'],
            'reported_at' => $data['reported_at'],
            'resolved_at' => $data['resolved_at'] ?? null,
            'status' => $data['status'],
            'severity' => $data['severity'],
            'driver_user_id' => $driverId,
            'responsible_organization_id' => $responsibleId,
            'location' => $data['location'] ?? null,
            'police_reference' => $data['police_reference'] ?? null,
            'insurance_claim_reference' => $data['insurance_claim_reference'] ?? null,
            'description' => $data['description'],
            'primary_document_id' => $document->id,
            'recorded_by_user_id' => $actor->id,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleIncident $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'incident_public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'incident_revision' => (int) $record->revision,
                'incident_type' => $record->incident_type,
                'status' => $record->status,
                'severity' => $record->severity,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleIncident $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'incident_record' => [
                'public_id' => $record->public_id,
                'record_uid' => $record->record_uid,
                'revision' => (int) $record->revision,
                'incident_type' => $record->incident_type,
                'occurred_at' => $record->occurred_at?->toIso8601String(),
                'reported_at' => $record->reported_at?->toIso8601String(),
                'resolved_at' => $record->resolved_at?->toIso8601String(),
                'status' => $record->status,
                'severity' => $record->severity,
                'driver_user_id' => $record->driver_user_id,
                'responsible_organization_id' => $record->responsible_organization_id,
                'location' => $record->location,
                'police_reference' => $record->police_reference,
                'insurance_claim_reference' => $record->insurance_claim_reference,
                'description' => $record->description,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
