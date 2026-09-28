<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleProvisionAgreement;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleProvisionEvidenceService
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
            $record = VehicleProvisionAgreement::query()->create($this->attributes($vehicle, $data, $organizationId, $actor) + [
                'revision' => 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_provision_evidence_registered', (string) $data['reason']);

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
            $previous = VehicleProvisionAgreement::query()->where('public_id', $recordPublicId)
                ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
                ->lockForUpdate()->first();
            if (! $previous instanceof VehicleProvisionAgreement) {
                throw (new ModelNotFoundException)->setModel(VehicleProvisionAgreement::class, [$recordPublicId]);
            }
            $latest = VehicleProvisionAgreement::query()->where('agreement_uid', $previous->agreement_uid)
                ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
                ->orderByDesc('revision')->lockForUpdate()->first();
            if (! $latest instanceof VehicleProvisionAgreement
                || (int) $latest->revision !== (int) $data['expected_provision_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle provision evidence revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleProvisionAgreement::query()->create($this->attributes($vehicle, $data, $organizationId, $actor) + [
                'agreement_uid' => $latest->agreement_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_provision_evidence_revised', (string) $data['reason']);

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
    private function attributes(Vehicle $vehicle, array $data, int $organizationId, User $actor): array
    {
        foreach (['provider', 'recipient'] as $party) {
            $type = $data[$party.'_type'];
            $partyOrganizationId = $data[$party.'_organization_id'] ?? null;
            $partyUserId = $data[$party.'_user_id'] ?? null;
            if ($type === 'organization') {
                abort_if($partyUserId !== null || (int) $partyOrganizationId !== $organizationId, 422, 'Organization party must match the active organization.');
            } else {
                abort_if($partyOrganizationId !== null || $partyUserId === null, 422, 'Provide one driver party.');
                $isMember = OrganizationMembership::query()->where('organization_id', $organizationId)
                    ->where('user_id', (int) $partyUserId)->where('status', OrganizationMembership::STATUS_ACTIVE)
                    ->where('valid_from', '<=', now())
                    ->where(static fn (Builder $query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
                    ->exists();
                abort_unless($isMember, 422, 'Driver party must be an active member of the organization.');
            }
        }

        return [
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'provider_type' => $data['provider_type'],
            'provider_organization_id' => $data['provider_organization_id'] ?? null,
            'provider_user_id' => $data['provider_user_id'] ?? null,
            'recipient_type' => $data['recipient_type'],
            'recipient_organization_id' => $data['recipient_organization_id'] ?? null,
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'provision_mode' => $data['provision_mode'],
            'agreement_number' => $data['agreement_number'] ?? null,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?? null,
            'status' => $data['status'],
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleProvisionAgreement $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'provision_public_id' => $record->public_id,
                'agreement_uid' => $record->agreement_uid,
                'provision_revision' => (int) $record->revision,
                'provision_mode' => $record->provision_mode,
                'status' => $record->status,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleProvisionAgreement $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'provision_agreement' => [
                'public_id' => $record->public_id,
                'agreement_uid' => $record->agreement_uid,
                'revision' => (int) $record->revision,
                'provision_mode' => $record->provision_mode,
                'status' => $record->status,
            ],
            'source_document' => [
                'public_id' => $document->public_id,
                'verification_status' => $document->verification_status,
                'revision' => (int) $document->revision,
            ],
        ];
    }
}
