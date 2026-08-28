<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelSurcharge;
use App\Modules\Fuel\Models\FuelSurchargeRecipientRate;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelSurchargeManagementService
{
    public function internalIndex(int $organizationId): Collection
    {
        return FuelSurcharge::query()
            ->where('owner_organization_id', $organizationId)
            ->with(['recipientRates' => static fn ($query) => $query
                ->orderByDesc('valid_from')])
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->get();
    }

    public function create(
        int $organizationId,
        User $actor,
        array $data,
    ): FuelSurcharge {
        $this->assertCustomerRelationship(
            (int) $data['customer_relationship_id'],
            $organizationId,
            'customer_relationship_id',
        );
        $this->assertRecipientShapes($organizationId, $data['recipients']);

        return DB::transaction(function () use (
            $organizationId,
            $actor,
            $data,
        ): FuelSurcharge {
            $validFrom = CarbonImmutable::parse($data['valid_from']);

            FuelSurcharge::query()
                ->where('owner_organization_id', $organizationId)
                ->where(
                    'customer_relationship_id',
                    (int) $data['customer_relationship_id'],
                )
                ->where('status', FuelSurcharge::STATUS_ACTIVE)
                ->lockForUpdate()
                ->get()
                ->each(function (FuelSurcharge $existing) use ($validFrom): void {
                    if (! $existing->valid_from->lessThan($validFrom)) {
                        throw ValidationException::withMessages([
                            'valid_from' => [
                                'The new rate must start after the current active rate.',
                            ],
                        ]);
                    }

                    FuelSurchargeRecipientRate::query()
                        ->where('fuel_surcharge_id', $existing->getKey())
                        ->where(
                            'status',
                            FuelSurchargeRecipientRate::STATUS_ACTIVE,
                        )
                        ->update([
                            'valid_until' => $validFrom
                                ->subDay()
                                ->toDateString(),
                            'status' => FuelSurchargeRecipientRate::STATUS_ENDED,
                            'updated_at' => now(),
                        ]);

                    $existing->forceFill([
                        'valid_until' => $validFrom->subDay()->toDateString(),
                        'status' => FuelSurcharge::STATUS_ENDED,
                        'lock_version' => (int) $existing->lock_version + 1,
                    ])->save();
                });

            $surcharge = FuelSurcharge::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'customer_relationship_id' => (int) $data['customer_relationship_id'],
                'billing_rate_per_actual_km' => $data['billing_rate_per_actual_km'],
                'currency' => 'CZK',
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'] ?? null,
                'status' => FuelSurcharge::STATUS_ACTIVE,
                'lock_version' => 1,
                'note' => $data['note'] ?? null,
                'created_by_user_id' => (int) $actor->getAuthIdentifier(),
            ]);

            foreach ($data['recipients'] as $recipient) {
                FuelSurchargeRecipientRate::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'fuel_surcharge_id' => $surcharge->getKey(),
                    'recipient_type' => $recipient['recipient_type'],
                    'driver_organization_assignment_id' => $recipient['driver_organization_assignment_id'] ?? null,
                    'carrier_relationship_id' => $recipient['carrier_relationship_id'] ?? null,
                    'payout_rate_per_actual_km' => $recipient['payout_rate_per_actual_km'],
                    'valid_from' => $data['valid_from'],
                    'valid_until' => $data['valid_until'] ?? null,
                    'status' => FuelSurchargeRecipientRate::STATUS_ACTIVE,
                    'note' => $recipient['note'] ?? null,
                    'created_by_user_id' => (int) $actor->getAuthIdentifier(),
                ]);
            }

            return $surcharge->load('recipientRates');
        });
    }

    public function internalPayload(FuelSurcharge $surcharge): array
    {
        return [
            'public_id' => $surcharge->public_id,
            'customer_relationship_id' => (int) $surcharge->customer_relationship_id,
            'billing_rate_per_actual_km' => $surcharge->billing_rate_per_actual_km,
            'currency' => $surcharge->currency,
            'valid_from' => $surcharge->valid_from?->toDateString(),
            'valid_until' => $surcharge->valid_until?->toDateString(),
            'status' => $surcharge->status,
            'lock_version' => (int) $surcharge->lock_version,
            'recipients' => $surcharge->recipientRates->map(
                fn (FuelSurchargeRecipientRate $rate): array => [
                    'public_id' => $rate->public_id,
                    'recipient_type' => $rate->recipient_type,
                    'driver_organization_assignment_id' => $rate->driver_organization_assignment_id,
                    'carrier_relationship_id' => $rate->carrier_relationship_id,
                    'payout_rate_per_actual_km' => $rate->payout_rate_per_actual_km,
                    'margin_per_actual_km' => bcsub(
                        (string) $surcharge->billing_rate_per_actual_km,
                        (string) $rate->payout_rate_per_actual_km,
                        4,
                    ),
                    'valid_from' => $rate->valid_from?->toDateString(),
                    'valid_until' => $rate->valid_until?->toDateString(),
                    'status' => $rate->status,
                ],
            )->values()->all(),
        ];
    }

    private function assertRecipientShapes(
        int $organizationId,
        array $recipients,
    ): void {
        $identities = [];

        foreach ($recipients as $index => $recipient) {
            $type = $recipient['recipient_type'];
            $driverId = $recipient['driver_organization_assignment_id'] ?? null;
            $carrierId = $recipient['carrier_relationship_id'] ?? null;

            if ($type === FuelSurchargeRecipientRate::TYPE_OWN_DRIVER) {
                if ($driverId === null || $carrierId !== null) {
                    $this->invalidRecipient($index);
                }

                $exists = DB::table('driver_organization_assignments')
                    ->where('id', (int) $driverId)
                    ->where('organization_id', $organizationId)
                    ->exists();

                if (! $exists) {
                    $this->invalidRecipient($index);
                }

                $identity = "driver:{$driverId}";
            } else {
                if ($carrierId === null || $driverId !== null) {
                    $this->invalidRecipient($index);
                }

                $this->assertCarrierRelationship(
                    (int) $carrierId,
                    $organizationId,
                    "recipients.{$index}.carrier_relationship_id",
                );
                $identity = "carrier:{$carrierId}";
            }

            if (isset($identities[$identity])) {
                throw ValidationException::withMessages([
                    "recipients.{$index}" => [
                        'The recipient may only be selected once.',
                    ],
                ]);
            }

            $identities[$identity] = true;
        }
    }

    private function assertCustomerRelationship(
        int $relationshipId,
        int $organizationId,
        string $field,
    ): void {
        $visible = DB::table('organization_relationships')
            ->where('id', $relationshipId)
            ->where('target_organization_id', $organizationId)
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where('status', OrganizationRelationship::STATUS_ACTIVE)
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->exists();

        if (! $visible) {
            throw ValidationException::withMessages([
                $field => ['The relationship is outside the active organization.'],
            ]);
        }
    }

    private function assertCarrierRelationship(
        int $relationshipId,
        int $organizationId,
        string $field,
    ): void {
        $visible = DB::table('organization_relationships')
            ->where('id', $relationshipId)
            ->where('source_organization_id', $organizationId)
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where('status', OrganizationRelationship::STATUS_ACTIVE)
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->exists();

        if (! $visible) {
            throw ValidationException::withMessages([
                $field => ['The carrier is not active for this organization.'],
            ]);
        }
    }

    private function invalidRecipient(int $index): never
    {
        throw ValidationException::withMessages([
            "recipients.{$index}" => [
                'The selected recipient type and identity do not match.',
            ],
        ]);
    }
}
