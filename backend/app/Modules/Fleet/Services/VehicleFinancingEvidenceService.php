<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleFinancingAgreement;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleFinancingEvidenceService
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
            $record = VehicleFinancingAgreement::query()->create($this->attributes($vehicle, $data, $organizationId, $actor) + [
                'revision' => 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_financing_evidence_registered', (string) $data['reason']);

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
            $previous = VehicleFinancingAgreement::query()->where('public_id', $recordPublicId)
                ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
                ->lockForUpdate()->first();
            if (! $previous instanceof VehicleFinancingAgreement) {
                throw (new ModelNotFoundException)->setModel(VehicleFinancingAgreement::class, [$recordPublicId]);
            }
            $latest = VehicleFinancingAgreement::query()->where('financing_uid', $previous->financing_uid)
                ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
                ->orderByDesc('revision')->lockForUpdate()->first();
            if (! $latest instanceof VehicleFinancingAgreement
                || (int) $latest->revision !== (int) $data['expected_financing_revision']
                || $latest->public_id !== $recordPublicId) {
                throw new ConflictHttpException('Vehicle financing evidence revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $record = VehicleFinancingAgreement::query()->create($this->attributes($vehicle, $data, $organizationId, $actor) + [
                'financing_uid' => $latest->financing_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $record, $document, $organizationId, $actor, 'vehicle_financing_evidence_revised', (string) $data['reason']);

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
        $financierOrganizationId = $data['financier_organization_id'] ?? null;
        $externalFinancier = $data['external_financier_name'] ?? null;
        abort_if(($financierOrganizationId === null) === ($externalFinancier === null), 422, 'Provide exactly one financier.');
        abort_if($financierOrganizationId !== null && (int) $financierOrganizationId !== $organizationId, 422, 'Financier organization must match the active organization.');
        $debtorOrganizationId = $data['debtor_organization_id'] ?? null;
        $debtorUserId = $data['debtor_user_id'] ?? null;
        if ($data['debtor_type'] === 'organization') {
            abort_if($debtorUserId !== null || (int) $debtorOrganizationId !== $organizationId, 422, 'Debtor organization must match the active organization.');
        } else {
            abort_if($debtorOrganizationId !== null || $debtorUserId === null, 422, 'Provide one debtor driver.');
            $isMember = OrganizationMembership::query()->where('organization_id', $organizationId)
                ->where('user_id', (int) $debtorUserId)->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->where('valid_from', '<=', now())
                ->where(static fn (Builder $query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
                ->exists();
            abort_unless($isMember, 422, 'Debtor driver must be an active member of the organization.');
        }

        return [
            'vehicle_id' => $vehicle->id,
            'organization_context_id' => $organizationId,
            'financing_type' => $data['financing_type'],
            'financier_organization_id' => $financierOrganizationId,
            'external_financier_name' => $externalFinancier,
            'debtor_type' => $data['debtor_type'],
            'debtor_organization_id' => $debtorOrganizationId,
            'debtor_user_id' => $debtorUserId,
            'agreement_number' => $data['agreement_number'] ?? null,
            'effective_from' => $data['effective_from'],
            'effective_until' => $data['effective_until'] ?? null,
            'currency' => $data['currency'],
            'total_amount' => $data['total_amount'] ?? null,
            'initial_payment_amount' => $data['initial_payment_amount'] ?? null,
            'residual_value_amount' => $data['residual_value_amount'] ?? null,
            'status' => $data['status'],
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleFinancingAgreement $record, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'financing_public_id' => $record->public_id,
                'financing_uid' => $record->financing_uid,
                'financing_revision' => (int) $record->revision,
                'financing_type' => $record->financing_type,
                'status' => $record->status,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleFinancingAgreement $record, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'financing_agreement' => [
                'public_id' => $record->public_id,
                'financing_uid' => $record->financing_uid,
                'revision' => (int) $record->revision,
                'financing_type' => $record->financing_type,
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
