<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleProvisionAgreement;
use App\Modules\Fleet\Models\VehicleProvisionPrice;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleProvisionPriceAdministrationService
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
            $agreement = $this->lockCurrentAgreement($vehicle, $data, $organizationId);
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $this->assertDates($agreement, $data);
            $price = VehicleProvisionPrice::query()->create($this->attributes($agreement, $data, $actor) + ['revision' => 1]);
            $this->advanceAndAudit($vehicle, $agreement, $price, $document, $organizationId, $actor, 'vehicle_provision_price_registered', (string) $data['reason']);

            return $this->result($vehicle, $agreement, $price, $document);
        });
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function revise(string $vehiclePublicId, string $pricePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);
        $this->readService->show($vehiclePublicId, $organizationId, $actor);

        return DB::transaction(function () use ($vehiclePublicId, $pricePublicId, $data, $organizationId, $actor): array {
            $vehicle = $this->lockVisibleVehicle($vehiclePublicId, $organizationId);
            $this->assertRevision($vehicle, (int) $data['expected_revision']);
            $agreement = $this->lockCurrentAgreement($vehicle, $data, $organizationId);
            $previous = VehicleProvisionPrice::query()->where('public_id', $pricePublicId)
                ->whereHas('agreement', fn (Builder $q) => $q->where('vehicle_id', $vehicle->id)
                    ->where('organization_context_id', $organizationId)->where('agreement_uid', $agreement->agreement_uid))
                ->lockForUpdate()->first();
            if (! $previous instanceof VehicleProvisionPrice) {
                throw (new ModelNotFoundException)->setModel(VehicleProvisionPrice::class, [$pricePublicId]);
            }
            $latest = VehicleProvisionPrice::query()->where('price_uid', $previous->price_uid)
                ->whereHas('agreement', fn (Builder $q) => $q->where('vehicle_id', $vehicle->id)
                    ->where('organization_context_id', $organizationId)->where('agreement_uid', $agreement->agreement_uid))
                ->orderByDesc('revision')->lockForUpdate()->first();
            if (! $latest instanceof VehicleProvisionPrice || $latest->public_id !== $pricePublicId
                || (int) $latest->revision !== (int) $data['expected_price_revision']) {
                throw new ConflictHttpException('Vehicle provision price revision is stale.');
            }
            $document = $this->lockVerifiedDocument($vehicle, (string) $data['source_document_public_id'], $organizationId);
            $this->assertDates($agreement, $data);
            $price = VehicleProvisionPrice::query()->create($this->attributes($agreement, $data, $actor) + [
                'price_uid' => $latest->price_uid,
                'revision' => (int) $latest->revision + 1,
            ]);
            $this->advanceAndAudit($vehicle, $agreement, $price, $document, $organizationId, $actor, 'vehicle_provision_price_revised', (string) $data['reason']);

            return $this->result($vehicle, $agreement, $price, $document);
        });
    }

    private function lockVisibleVehicle(string $publicId, int $organizationId): Vehicle
    {
        $vehicle = Vehicle::query()->where('public_id', $publicId)
            ->where(static function (Builder $q) use ($organizationId): void {
                $q->whereHas('ownerships', fn (Builder $r) => $r->where('organization_context_id', $organizationId))
                    ->orWhereHas('responsibilities', fn (Builder $r) => $r->where('organization_context_id', $organizationId));
            })->lockForUpdate()->first();
        if (! $vehicle instanceof Vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
        }

        return $vehicle;
    }

    /** @param array<string, mixed> $data */
    private function lockCurrentAgreement(Vehicle $vehicle, array $data, int $organizationId): VehicleProvisionAgreement
    {
        $publicId = (string) $data['provision_public_id'];
        $agreement = VehicleProvisionAgreement::query()->where('public_id', $publicId)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->lockForUpdate()->first();
        if (! $agreement instanceof VehicleProvisionAgreement) {
            throw (new ModelNotFoundException)->setModel(VehicleProvisionAgreement::class, [$publicId]);
        }
        $latest = VehicleProvisionAgreement::query()->where('agreement_uid', $agreement->agreement_uid)
            ->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)
            ->orderByDesc('revision')->lockForUpdate()->first();
        if (! $latest instanceof VehicleProvisionAgreement || $latest->public_id !== $publicId
            || (int) $latest->revision !== (int) $data['expected_provision_revision']) {
            throw new ConflictHttpException('Vehicle provision agreement revision is stale.');
        }

        return $agreement;
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

    private function assertRevision(Vehicle $vehicle, int $expected): void
    {
        if ((int) $vehicle->current_revision !== $expected) {
            throw new ConflictHttpException('Vehicle revision is stale.');
        }
    }

    /** @param array<string, mixed> $data */
    private function assertDates(VehicleProvisionAgreement $agreement, array $data): void
    {
        if ((string) $data['valid_from'] < $agreement->valid_from->format('Y-m-d')
            || ($agreement->valid_until !== null && (($data['valid_until'] ?? null) === null
                || (string) $data['valid_until'] > $agreement->valid_until->format('Y-m-d')))) {
            abort(422, 'Price dates must be within the provision agreement.');
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function attributes(VehicleProvisionAgreement $agreement, array $data, User $actor): array
    {
        return [
            'vehicle_provision_agreement_id' => $agreement->id,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'billing_period' => $data['billing_period'],
            'billing_mode' => $data['billing_mode'],
            'vat_mode' => $data['vat_mode'],
            'vat_rate_basis_points' => $data['vat_rate_basis_points'] ?? null,
            'recorded_by_user_id' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function advanceAndAudit(Vehicle $vehicle, VehicleProvisionAgreement $agreement, VehicleProvisionPrice $price, VehicleDocument $document, int $organizationId, User $actor, string $type, string $reason): void
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
                'price_public_id' => $price->public_id,
                'price_uid' => $price->price_uid,
                'price_revision' => (int) $price->revision,
                'provision_public_id' => $agreement->public_id,
                'agreement_uid' => $agreement->agreement_uid,
                'provision_revision' => (int) $agreement->revision,
                'source_document_public_id' => $document->public_id,
                'source_document_revision' => (int) $document->revision,
            ],
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function result(Vehicle $vehicle, VehicleProvisionAgreement $agreement, VehicleProvisionPrice $price, VehicleDocument $document): array
    {
        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'revision' => (int) $vehicle->current_revision],
            'provision_agreement' => ['public_id' => $agreement->public_id, 'agreement_uid' => $agreement->agreement_uid, 'revision' => (int) $agreement->revision],
            'provision_price' => ['public_id' => $price->public_id, 'price_uid' => $price->price_uid, 'revision' => (int) $price->revision],
            'source_document' => ['public_id' => $document->public_id, 'revision' => (int) $document->revision],
        ];
    }
}
