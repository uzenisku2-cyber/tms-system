<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleDocument;
use App\Modules\Fleet\Models\VehicleRecordFieldStatus;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use App\Modules\Fleet\Models\VehicleResponsibility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ProgressiveVehicleRecordService
{
    private const FIELD_KEYS = [
        'registration_number', 'vin', 'manufacturer', 'model', 'year', 'fuel_type', 'mileage',
        'registration_certificate', 'ownership', 'financing', 'insurance', 'technical_inspection',
    ];

    /** @param array<string, mixed> $data */
    public function create(array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($data, $organizationId, $actor): array {
            $vehicle = Vehicle::query()->create([
                'user_id' => null,
                'registration_number' => $this->normalized($data['registration_number'] ?? null),
                'vin' => $this->normalized($data['vin'] ?? null),
                'manufacturer' => $this->text($data['manufacturer'] ?? null),
                'model' => $this->text($data['model'] ?? null),
                'year' => $data['year'] ?? null,
                'fuel_type' => $this->text($data['fuel_type'] ?? null),
                'mileage' => (int) ($data['mileage'] ?? 0),
                'odometer_unit' => 'km',
                'lifecycle_status' => 'active',
                'current_revision' => 1,
                'active' => true,
            ]);

            VehicleResponsibility::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'responsibility_type' => 'operational_organization',
                'party_type' => 'organization',
                'party_organization_id' => $organizationId,
                'valid_from' => now(),
                'source' => 'progressive_vehicle_record',
                'status' => 'active',
                'recorded_by_user_id' => $actor->id,
                'reason' => (string) $data['reason'],
                'revision' => 1,
            ]);

            $mileageWasProvided = array_key_exists('mileage', $data) && $data['mileage'] !== null;
            foreach (self::FIELD_KEYS as $fieldKey) {
                $hasValue = match ($fieldKey) {
                    'mileage' => $mileageWasProvided,
                    default => in_array($fieldKey, ['registration_number', 'vin', 'manufacturer', 'model', 'year', 'fuel_type'], true)
                        && $vehicle->getAttribute($fieldKey) !== null,
                };
                VehicleRecordFieldStatus::query()->create([
                    'vehicle_id' => $vehicle->id,
                    'organization_context_id' => $organizationId,
                    'field_key' => $fieldKey,
                    'status' => $hasValue ? VehicleRecordFieldStatus::STATUS_UNVERIFIED : VehicleRecordFieldStatus::STATUS_PENDING_DOCUMENT,
                    'reason' => $hasValue ? 'Entered without documentary verification.' : 'To be completed when the source document is available.',
                    'recorded_by_user_id' => $actor->id,
                    'revision' => 1,
                ]);
            }

            VehicleRegistryEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'actor_user_id' => $actor->id,
                'event_type' => 'progressive_vehicle_record_created',
                'vehicle_revision' => 1,
                'reason' => (string) $data['reason'],
                'payload' => ['incomplete_record_allowed' => true, 'field_keys' => self::FIELD_KEYS],
                'occurred_at' => now(),
            ]);

            $vehicle->refresh();

            return $this->result($vehicle, $organizationId);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(string $publicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);

        return DB::transaction(function () use ($publicId, $data, $organizationId, $actor): array {
            $vehicle = Vehicle::query()->where('public_id', $publicId)->lockForUpdate()->first();
            if (! $vehicle instanceof Vehicle || ! $vehicle->responsibilities()->where('organization_context_id', $organizationId)->exists()) {
                throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
            }
            if ((int) $vehicle->current_revision !== (int) $data['expected_revision']) {
                throw new ConflictHttpException('Vehicle revision is stale.');
            }

            /** @var array<string, mixed> $fields */
            $fields = $data['fields'];
            $unsupported = array_diff(array_keys($fields), array_slice(self::FIELD_KEYS, 0, 7));
            if ($unsupported !== []) {
                throw ValidationException::withMessages(['fields' => ['Unsupported vehicle field.']]);
            }

            $document = null;
            if (($data['source_document_public_id'] ?? null) !== null) {
                $document = VehicleDocument::query()
                    ->where('public_id', $data['source_document_public_id'])
                    ->where('vehicle_id', $vehicle->id)
                    ->where('organization_context_id', $organizationId)
                    ->first();
                if (! $document instanceof VehicleDocument) {
                    throw ValidationException::withMessages(['source_document_public_id' => ['The source document does not belong to this vehicle and organization.']]);
                }
            }

            $normalizedFields = [];
            foreach ($fields as $fieldKey => $value) {
                $normalizedFields[$fieldKey] = match ($fieldKey) {
                    'registration_number', 'vin' => $this->normalized($value),
                    'manufacturer', 'model', 'fuel_type' => $this->text($value),
                    'year' => $value === null ? null : (int) $value,
                    'mileage' => $value === null ? 0 : (int) $value,
                    default => $value,
                };
            }

            $nextRegistration = array_key_exists('registration_number', $normalizedFields) ? $normalizedFields['registration_number'] : $vehicle->registration_number;
            $nextVin = array_key_exists('vin', $normalizedFields) ? $normalizedFields['vin'] : $vehicle->vin;
            if ($nextRegistration === null && $nextVin === null) {
                throw ValidationException::withMessages(['fields' => ['Registration number or VIN must remain available.']]);
            }
            foreach (['registration_number', 'vin'] as $identityField) {
                if (! array_key_exists($identityField, $normalizedFields) || $normalizedFields[$identityField] === null) {
                    continue;
                }
                if (Vehicle::query()->where($identityField, $normalizedFields[$identityField])->whereKeyNot($vehicle->id)->exists()) {
                    throw ValidationException::withMessages(["fields.$identityField" => ['The value is already used by another vehicle.']]);
                }
            }

            $before = [];
            $after = [];
            $statusChanged = false;
            foreach ($normalizedFields as $fieldKey => $value) {
                $before[$fieldKey] = $fieldKey === 'mileage' && $fields[$fieldKey] === null ? null : $vehicle->getAttribute($fieldKey);
                $after[$fieldKey] = $fields[$fieldKey];
                $targetStatus = $fields[$fieldKey] === null
                    ? VehicleRecordFieldStatus::STATUS_PENDING_DOCUMENT
                    : ($document?->verification_status === 'verified' ? VehicleRecordFieldStatus::STATUS_VERIFIED : VehicleRecordFieldStatus::STATUS_UNVERIFIED);
                $status = VehicleRecordFieldStatus::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->where('organization_context_id', $organizationId)
                    ->where('field_key', $fieldKey)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($status->status !== $targetStatus || $status->reason !== $data['reason']) {
                    $status->update([
                        'status' => $targetStatus,
                        'reason' => $data['reason'],
                        'recorded_by_user_id' => $actor->id,
                        'revision' => (int) $status->revision + 1,
                    ]);
                    $statusChanged = true;
                }
            }

            $valueChanged = collect($normalizedFields)->contains(fn (mixed $value, string $fieldKey): bool => $vehicle->getAttribute($fieldKey) !== $value);
            if (! $valueChanged && ! $statusChanged) {
                return $this->result($vehicle, $organizationId);
            }

            $nextRevision = (int) $vehicle->current_revision + 1;
            $vehicle->update([...$normalizedFields, 'current_revision' => $nextRevision]);
            VehicleRegistryEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'actor_user_id' => $actor->id,
                'event_type' => 'progressive_vehicle_record_updated',
                'vehicle_revision' => $nextRevision,
                'reason' => (string) $data['reason'],
                'payload' => [
                    'before' => $before,
                    'after' => $after,
                    'source_document_public_id' => $document?->public_id,
                    'source_document_verification_status' => $document?->verification_status,
                ],
                'occurred_at' => now(),
            ]);

            $vehicle->refresh();

            return $this->result($vehicle, $organizationId);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateFieldStatus(string $publicId, string $fieldKey, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);
        abort_unless(in_array($fieldKey, self::FIELD_KEYS, true), 404);

        return DB::transaction(function () use ($publicId, $fieldKey, $data, $organizationId, $actor): array {
            $vehicle = Vehicle::query()->where('public_id', $publicId)->lockForUpdate()->first();
            if (! $vehicle instanceof Vehicle || ! $vehicle->responsibilities()->where('organization_context_id', $organizationId)->exists()) {
                throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
            }
            if ((int) $vehicle->current_revision !== (int) $data['expected_revision']) {
                throw new ConflictHttpException('Vehicle revision is stale.');
            }

            $status = VehicleRecordFieldStatus::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('organization_context_id', $organizationId)
                ->where('field_key', $fieldKey)
                ->lockForUpdate()
                ->firstOrFail();
            if ($status->status === $data['status'] && $status->reason === $data['reason']) {
                return $this->result($vehicle, $organizationId);
            }

            $nextRevision = (int) $vehicle->current_revision + 1;
            $status->update(['status' => $data['status'], 'reason' => $data['reason'], 'recorded_by_user_id' => $actor->id, 'revision' => (int) $status->revision + 1]);
            $vehicle->update(['current_revision' => $nextRevision]);
            VehicleRegistryEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'actor_user_id' => $actor->id,
                'event_type' => 'vehicle_record_field_status_changed',
                'vehicle_revision' => $nextRevision,
                'reason' => (string) $data['reason'],
                'payload' => ['field_key' => $fieldKey, 'status' => $data['status']],
                'occurred_at' => now(),
            ]);

            $vehicle->refresh();

            return $this->result($vehicle, $organizationId);
        });
    }

    private function normalized(mixed $value): ?string
    {
        $text = $this->text($value);

        return $text === null ? null : Str::upper($text);
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function result(Vehicle $vehicle, int $organizationId): array
    {
        $statuses = VehicleRecordFieldStatus::query()->where('vehicle_id', $vehicle->id)->where('organization_context_id', $organizationId)->orderBy('field_key')->get();
        $complete = $statuses->filter(static fn (VehicleRecordFieldStatus $status): bool => in_array($status->status, [VehicleRecordFieldStatus::STATUS_VERIFIED, VehicleRecordFieldStatus::STATUS_NOT_APPLICABLE], true))->count();

        return [
            'vehicle' => ['public_id' => $vehicle->public_id, 'registration_number' => $vehicle->registration_number, 'vin' => $vehicle->vin, 'revision' => (int) $vehicle->current_revision],
            'field_statuses' => $statuses->toArray(),
            'completeness' => ['complete' => $complete, 'total' => $statuses->count(), 'percentage' => $statuses->count() === 0 ? 0 : (int) floor(($complete * 100) / $statuses->count())],
        ];
    }
}
