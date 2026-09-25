<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleDocumentEvidenceService
{
    /** @param array<string, mixed> $data */
    public function store(string $vehiclePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);

            $document = VehicleDocument::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'document_type' => $data['document_type'],
                'title' => $data['title'],
                'storage_reference' => $data['storage_reference'],
                'issue_date' => $data['issue_date'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'verification_status' => 'unverified',
                'access_classification' => $data['access_classification'],
                'uploaded_by_user_id' => $actor->id,
                'revision' => 1,
            ]);

            $nextRevision = (int) $vehicle->current_revision + 1;
            $vehicle->update(['current_revision' => $nextRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextRevision, 'vehicle_document_evidence_registered', (string) $data['reason'], [
                'document_public_id' => $document->public_id,
                'document_type' => $document->document_type,
                'verification_status' => $document->verification_status,
                'document_revision' => 1,
            ]);

            return $this->result($vehicle, $document);
        });
    }

    /** @param array<string, mixed> $data */
    public function review(string $vehiclePublicId, string $documentPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($vehiclePublicId, $documentPublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertVehicleRevision($vehicle, (int) $data['expected_revision']);
            $document = VehicleDocument::query()
                ->where('public_id', $documentPublicId)
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->first();
            if (! $document instanceof VehicleDocument) {
                throw (new ModelNotFoundException)->setModel(VehicleDocument::class, [$documentPublicId]);
            }
            if ((int) $document->revision !== (int) $data['expected_document_revision']) {
                throw new ConflictHttpException('Vehicle document revision is stale.');
            }
            if ($document->verification_status === $data['verification_status']) {
                return $this->result($vehicle, $document);
            }

            $nextDocumentRevision = (int) $document->revision + 1;
            $nextVehicleRevision = (int) $vehicle->current_revision + 1;
            $previousStatus = $document->verification_status;
            $document->update([
                'verification_status' => $data['verification_status'],
                'revision' => $nextDocumentRevision,
            ]);
            $vehicle->update(['current_revision' => $nextVehicleRevision]);
            $this->recordEvent($vehicle, $organizationId, $actor, $nextVehicleRevision, 'vehicle_document_evidence_reviewed', (string) $data['reason'], [
                'document_public_id' => $document->public_id,
                'previous_verification_status' => $previousStatus,
                'verification_status' => $document->verification_status,
                'document_revision' => $nextDocumentRevision,
            ]);

            return $this->result($vehicle, $document);
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
    private function result(Vehicle $vehicle, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'document' => [
                'public_id' => $document->public_id,
                'document_type' => $document->document_type,
                'title' => $document->title,
                'storage_reference' => $document->storage_reference,
                'issue_date' => $document->issue_date?->toDateString(),
                'valid_from' => $document->valid_from?->toDateString(),
                'valid_until' => $document->valid_until?->toDateString(),
                'verification_status' => $document->verification_status,
                'access_classification' => $document->access_classification,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
