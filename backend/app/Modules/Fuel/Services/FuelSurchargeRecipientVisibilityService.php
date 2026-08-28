<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelSurchargeRecipientRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class FuelSurchargeRecipientVisibilityService
{
    public function ownRates(User $actor, int $organizationId): Collection
    {
        $externalOrganization = DB::table('organizations')
            ->where('id', $organizationId)
            ->where('type', '!=', 'master')
            ->exists();

        return FuelSurchargeRecipientRate::query()
            ->where(function ($query) use (
                $actor,
                $organizationId,
                $externalOrganization,
            ): void {
                $query->where(function ($driverQuery) use (
                    $actor,
                ): void {
                    $driverQuery
                        ->where(
                            'recipient_type',
                            FuelSurchargeRecipientRate::TYPE_OWN_DRIVER,
                        )
                        ->whereExists(function ($assignment) use ($actor): void {
                            $assignment->selectRaw('1')
                                ->from('driver_organization_assignments')
                                ->join(
                                    'drivers',
                                    'drivers.id',
                                    '=',
                                    'driver_organization_assignments.driver_id',
                                )
                                ->whereColumn(
                                    'driver_organization_assignments.id',
                                    'fuel_surcharge_recipient_rates.driver_organization_assignment_id',
                                )
                                ->where(
                                    'drivers.user_id',
                                    (int) $actor->getAuthIdentifier(),
                                )
                                ->where(
                                    'driver_organization_assignments.organization_id',
                                    $organizationId,
                                );
                        });
                });

                if (! $externalOrganization) {
                    return;
                }

                $query->orWhere(function ($carrierQuery) use ($organizationId): void {
                    $carrierQuery
                        ->where(
                            'recipient_type',
                            FuelSurchargeRecipientRate::TYPE_EXTERNAL_CARRIER,
                        )
                        ->whereExists(function ($relationship) use ($organizationId): void {
                            $relationship->selectRaw('1')
                                ->from('organization_relationships')
                                ->whereColumn(
                                    'organization_relationships.id',
                                    'fuel_surcharge_recipient_rates.carrier_relationship_id',
                                )
                                ->where(
                                    'target_organization_id',
                                    $organizationId,
                                )
                                ->where('relationship_type', 'subcontracting')
                                ->where('status', 'active');
                        });
                });
            })
            ->orderByDesc('valid_from')
            ->get();
    }

    public function recipientPayload(FuelSurchargeRecipientRate $rate): array
    {
        return [
            'public_id' => $rate->public_id,
            'recipient_type' => $rate->recipient_type,
            'payout_rate_per_actual_km' => $rate->payout_rate_per_actual_km,
            'currency' => 'CZK',
            'quantity_source' => 'actual_km',
            'valid_from' => $rate->valid_from?->toDateString(),
            'valid_until' => $rate->valid_until?->toDateString(),
            'status' => $rate->status,
        ];
    }
}
