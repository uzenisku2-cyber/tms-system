<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Fleet\Models\VehicleResponsibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleResponsibilityEvidenceService
{
    /** @param array<string, mixed> $data */
    public function store(string $vehiclePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);

            $responsibility = VehicleResponsibility::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'responsibility_type' => $data['responsibility_type'],
                'party_type' => $data['party_type'],
                'party_organization_id' => $data['party_type'] === 'organization' ? $data['party_organization_id'] : null,
                'party_user_id' => $data['party_type'] === 'user' ? $data['party_user_id'] : null,
                'external_party_name' => $data['party_type'] === 'external_party' ? $data['external_party_name'] : null,
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'] ?? null,
                'source' => 'document:'.$document->public_id,
                'status' => 'active',
                'recorded_by_user_id' => $actor->id,
                'reason' => $data['reason'],
                'revision' => 1,
            ]);

            $nextRevision = (int) $vehicle->current_revision + 1;
            $vehicle->update(['current_revision' => $nextRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextRevision, 'vehicle_responsibility_evidence_registered', (string) $data['reason'], [
                'responsibility_public_id' => $responsibility->public_id,
                'responsibility_type' => $responsibility->responsibility_type,
                'party_type' => $responsibility->party_type,
                'status' => $responsibility->status,
                'responsibility_revision' => 1,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ]);

            return $this->result($vehicle, $responsibility, $document);
        });
    }

    /** @param array<string, mixed> $data */
    public function review(string $vehiclePublicId, string $responsibilityPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $responsibilityPublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $responsibility = VehicleResponsibility::query()
                ->where('public_id', $responsibilityPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $responsibility instanceof VehicleResponsibility) {
                throw (new ModelNotFoundException)->setModel(VehicleResponsibility::class, [$responsibilityPublicId]);
            }
            if ((int) $responsibility->revision !== (int) $data['expected_responsibility_revision']) {
                throw new ConflictHttpException('Vehicle responsibility revision is stale.');
            }
            if ($responsibility->status === $data['status']) {
                return $this->result($vehicle, $responsibility, $document);
            }

            $previousStatus = $responsibility->status;
            $nextResponsibilityRevision = (int) $responsibility->revision + 1;
            $nextVehicleRevision = (int) $vehicle->current_revision + 1;
            $responsibility->update([
                'status' => $data['status'],
                'reason' => $data['reason'],
                'revision' => $nextResponsibilityRevision,
            ]);
            $vehicle->update(['current_revision' => $nextVehicleRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextVehicleRevision, 'vehicle_responsibility_evidence_reviewed', (string) $data['reason'], [
                'responsibility_public_id' => $responsibility->public_id,
                'previous_status' => $previousStatus,
                'status' => $responsibility->status,
                'responsibility_revision' => $nextResponsibilityRevision,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ]);

            return $this->result($vehicle, $responsibility, $document);
        });
    }

    private function lockVisibleVehicle(string $publicId, int $organizationId): Vehicle
    {
        $vehicle = Vehicle::query()
            ->where('public_id', $publicId)
            ->where(static function (Builder $query) use ($organizationId): void {
                $query->whereHas('ownerships', fn (Builder $relation) => $relation->where('organization_context_id', $organizationId))
                    ->orWhereHas('responsibilities', fn (Builder $relation) => $relation->where('organization_context_id', $organizationId));
            })
            ->lockForUpdate()
            ->first();
        if (! $vehicle instanceof Vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
        }

        return $vehicle;
    }

    private function lockVerifiedDocument(Vehicle $vehicle, string $publicId, int $organizationId): VehicleDocument
    {
        $document = VehicleDocument::query()
            ->where('public_id', $publicId)
            ->where('vehicle_id', $vehicle->id)
            ->where('organization_context_id', $organizationId)
            ->where('verification_status', 'verified')
            ->lockForUpdate()
            ->first();
        if (! $document instanceof VehicleDocument) {
            throw (new ModelNotFoundException)->setModel(VehicleDocument::class, [$publicId]);
        }

        return $document;
    }

    private function assertVehicleRevision(Vehicle $vehicle, int $expectedRevision): void
    {
        if ((int) $vehicle->current_revision !== $expectedRevision) {
            throw new ConflictHttpException('Vehicle revision is stale.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function recordEvent(Vehicle $vehicle, int $organizationId, User $actor, int $revision, string $type, string $reason, array $payload): void
    {
        VehicleRegistryEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'actor_user_id' => $actor->id,
            'event_type' => $type,
            'vehicle_revision' => $revision,
            'reason' => $reason,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleResponsibility $responsibility, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'responsibility' => [
                'public_id' => $responsibility->public_id,
                'responsibility_type' => $responsibility->responsibility_type,
                'party_type' => $responsibility->party_type,
                'party_organization_id' => $responsibility->party_organization_id,
                'party_user_id' => $responsibility->party_user_id,
                'external_party_name' => $responsibility->external_party_name,
                'valid_from' => $responsibility->valid_from?->toIso8601String(),
                'valid_until' => $responsibility->valid_until?->toIso8601String(),
                'source' => $responsibility->source,
                'status' => $responsibility->status,
                'revision' => (int) $responsibility->revision,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
