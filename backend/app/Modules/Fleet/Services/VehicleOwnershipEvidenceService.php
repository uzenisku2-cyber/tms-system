<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleOwnership;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleOwnershipEvidenceService
{
    /** @param array<string, mixed> $data */
    public function store(string $vehiclePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);

            $ownership = VehicleOwnership::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'owner_type' => $data['owner_type'],
                'owner_organization_id' => $data['owner_type'] === 'organization' ? $data['owner_organization_id'] : null,
                'owner_user_id' => $data['owner_type'] === 'user' ? $data['owner_user_id'] : null,
                'external_owner_name' => $data['owner_type'] === 'external_party' ? $data['external_owner_name'] : null,
                'ownership_share_basis_points' => $data['ownership_share_basis_points'],
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'] ?? null,
                'acquisition_basis' => $data['acquisition_basis'] ?? null,
                'verification_status' => 'unverified',
                'recorded_by_user_id' => $actor->id,
                'change_reason' => $data['reason'],
                'revision' => 1,
            ]);

            $nextRevision = (int) $vehicle->current_revision + 1;
            $vehicle->update(['current_revision' => $nextRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextRevision, 'vehicle_ownership_evidence_registered', (string) $data['reason'], [
                'ownership_public_id' => $ownership->public_id,
                'owner_type' => $ownership->owner_type,
                'ownership_share_basis_points' => (int) $ownership->ownership_share_basis_points,
                'verification_status' => $ownership->verification_status,
                'ownership_revision' => 1,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ]);

            return $this->result($vehicle, $ownership, $document);
        });
    }

    /** @param array<string, mixed> $data */
    public function review(string $vehiclePublicId, string $ownershipPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $ownershipPublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $ownership = VehicleOwnership::query()
                ->where('public_id', $ownershipPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $ownership instanceof VehicleOwnership) {
                throw (new ModelNotFoundException)->setModel(VehicleOwnership::class, [$ownershipPublicId]);
            }
            if ((int) $ownership->revision !== (int) $data['expected_ownership_revision']) {
                throw new ConflictHttpException('Vehicle ownership revision is stale.');
            }
            if ($ownership->verification_status === $data['verification_status']) {
                return $this->result($vehicle, $ownership, $document);
            }

            $previousStatus = $ownership->verification_status;
            $nextOwnershipRevision = (int) $ownership->revision + 1;
            $nextVehicleRevision = (int) $vehicle->current_revision + 1;
            $ownership->update([
                'verification_status' => $data['verification_status'],
                'change_reason' => $data['reason'],
                'revision' => $nextOwnershipRevision,
            ]);
            $vehicle->update(['current_revision' => $nextVehicleRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextVehicleRevision, 'vehicle_ownership_evidence_reviewed', (string) $data['reason'], [
                'ownership_public_id' => $ownership->public_id,
                'previous_verification_status' => $previousStatus,
                'verification_status' => $ownership->verification_status,
                'ownership_revision' => $nextOwnershipRevision,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ]);

            return $this->result($vehicle, $ownership, $document);
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
    private function result(Vehicle $vehicle, VehicleOwnership $ownership, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'ownership' => [
                'public_id' => $ownership->public_id,
                'owner_type' => $ownership->owner_type,
                'owner_organization_id' => $ownership->owner_organization_id,
                'owner_user_id' => $ownership->owner_user_id,
                'external_owner_name' => $ownership->external_owner_name,
                'ownership_share_basis_points' => (int) $ownership->ownership_share_basis_points,
                'valid_from' => $ownership->valid_from?->toIso8601String(),
                'valid_until' => $ownership->valid_until?->toIso8601String(),
                'acquisition_basis' => $ownership->acquisition_basis,
                'verification_status' => $ownership->verification_status,
                'revision' => (int) $ownership->revision,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
