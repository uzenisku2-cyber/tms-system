<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelCardEvent;
use App\Modules\Fuel\Models\FuelCardSettlementPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelCardManagementService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function visibleCards(int $organizationId): Collection
    {
        return FuelCard::query()->where(function ($query) use ($organizationId): void {
            $query->where('owner_organization_id', $organizationId)->orWhereHas('assignments', static function ($assignment) use ($organizationId): void {
                $assignment->where('responsible_organization_id', $organizationId);
            });
        })->with(['assignments' => static fn ($query) => $query->orderByDesc('valid_from'), 'settlementPolicies' => static fn ($query) => $query->orderByDesc('valid_from')])->orderBy('provider')->orderBy('masked_card_number')->get();
    }

    public function create(int $organizationId, User $actor, array $data): FuelCard
    {
        return DB::transaction(function () use ($organizationId, $actor, $data): FuelCard {
            $reason = (string) $data['reason'];
            unset($data['reason']);
            $card = FuelCard::query()->create([...$data, 'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'status' => 'active', 'currency' => strtoupper((string) ($data['currency'] ?? 'CZK')), 'lock_version' => 1, 'created_by_user_id' => (int) $actor->getAuthIdentifier()]);
            $this->event($card, 'created', $organizationId, $actor, $reason, null, $card->toArray());

            return $card->refresh();
        });
    }

    public function update(FuelCard $card, int $organizationId, User $actor, array $data): FuelCard
    {
        $this->assertOwned($card, $organizationId);

        return DB::transaction(function () use ($card, $organizationId, $actor, $data): FuelCard {
            $locked = FuelCard::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();
            $lockVersion = (int) $data['lock_version'];
            if ((int) $locked->lock_version !== $lockVersion) {
                throw ValidationException::withMessages(['lock_version' => ['The fuel card was changed by another operation.']]);
            }
            if (! empty($data['expires_at']) && $locked->valid_from->greaterThan($data['expires_at'])) {
                throw ValidationException::withMessages(['expires_at' => ['The card expiry cannot precede its validity start.']]);
            }

            $reason = (string) $data['reason'];
            $before = $locked->toArray();
            $editable = collect($data)->only([
                'label',
                'masked_card_number',
                'expires_at',
                'purchase_restrictions',
                'provider_status',
                'provider_status_verified_at',
                'provider_status_note',
            ])->all();
            $locked->forceFill([...$editable, 'lock_version' => $lockVersion + 1])->save();
            $this->event($locked, 'updated', $organizationId, $actor, $reason, $before, $locked->fresh()->toArray());

            return $locked->refresh();
        });
    }

    public function changeStatus(FuelCard $card, int $organizationId, User $actor, string $status, int $lockVersion, string $reason): FuelCard
    {
        $this->assertOwned($card, $organizationId);

        return DB::transaction(function () use ($card, $organizationId, $actor, $status, $lockVersion, $reason): FuelCard {
            $locked = FuelCard::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();
            if ((int) $locked->lock_version !== $lockVersion) {
                throw ValidationException::withMessages(['lock_version' => ['The fuel card was changed by another operation.']]);
            }
            $before = $locked->toArray();
            $locked->forceFill(['status' => $status, 'lock_version' => $lockVersion + 1])->save();
            $this->event($locked, 'status_changed', $organizationId, $actor, $reason, $before, $locked->fresh()->toArray());

            return $locked->refresh();
        });
    }

    public function assign(FuelCard $card, int $organizationId, User $actor, array $data): FuelCardAssignment
    {
        $this->assertOwned($card, $organizationId);
        $targetOrganizationId = (int) $data['responsible_organization_id'];
        $this->authorization->findManageableOrganization($actor, $organizationId, $targetOrganizationId);
        if (! empty($data['driver_id'])) {
            $this->authorization->findVisibleDriver($actor, $organizationId, (int) $data['driver_id']);
        }
        $this->validateAssignmentShape($data);

        return DB::transaction(function () use ($card, $organizationId, $actor, $data): FuelCardAssignment {
            FuelCard::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();
            $overlap = FuelCardAssignment::query()->where('fuel_card_id', $card->getKey())->where('status', 'active')->where('valid_from', '<=', $data['valid_until'] ?? '9999-12-31 23:59:59')->where(static function ($query) use ($data): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $data['valid_from']);
            })->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['valid_from' => ['The fuel card already has an overlapping active assignment.']]);
            }
            $assignment = FuelCardAssignment::query()->create([...$data, 'public_id' => (string) Str::uuid(), 'fuel_card_id' => $card->getKey(), 'status' => 'active', 'assigned_by_user_id' => (int) $actor->getAuthIdentifier()]);
            $this->event($card, 'assignment_created', $organizationId, $actor, (string) $data['reason'], null, $assignment->toArray(), $assignment);

            return $assignment->refresh();
        });
    }

    public function endAssignment(FuelCard $card, FuelCardAssignment $assignment, int $organizationId, User $actor, string $validUntil, string $reason): FuelCardAssignment
    {
        $this->assertOwned($card, $organizationId);
        if ((int) $assignment->fuel_card_id !== (int) $card->getKey()) {
            abort(404);
        }

        return DB::transaction(function () use ($card, $assignment, $organizationId, $actor, $validUntil, $reason): FuelCardAssignment {
            $locked = FuelCardAssignment::query()->whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active' || $locked->valid_from->greaterThan($validUntil)) {
                throw ValidationException::withMessages(['valid_until' => ['Only an active assignment may be ended after it starts.']]);
            }
            $before = $locked->toArray();
            $locked->forceFill(['status' => 'ended', 'valid_until' => $validUntil, 'reason' => $reason, 'ended_by_user_id' => (int) $actor->getAuthIdentifier()])->save();
            $this->event($card, 'assignment_ended', $organizationId, $actor, $reason, $before, $locked->fresh()->toArray(), $locked);

            return $locked->refresh();
        });
    }

    public function createPolicy(FuelCard $card, int $organizationId, User $actor, array $data): FuelCardSettlementPolicy
    {
        $this->assertOwned($card, $organizationId);
        $expectedVatMode = $data['settlement_target'] === 'driver'
            ? 'not_applicable'
            : 'counterparty_tax_profile';

        if ($data['vat_mode'] !== $expectedVatMode) {
            throw ValidationException::withMessages([
                'vat_mode' => ["The VAT mode must be {$expectedVatMode} for the selected settlement target."],
            ]);
        }

        return DB::transaction(function () use ($card, $organizationId, $actor, $data): FuelCardSettlementPolicy {
            $policy = FuelCardSettlementPolicy::query()->create([...$data, 'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'fuel_card_id' => $card->getKey(), 'created_by_user_id' => (int) $actor->getAuthIdentifier()]);
            $this->event($card, 'settlement_policy_created', $organizationId, $actor, (string) $data['reason'], null, $policy->toArray(), null, $policy);

            return $policy->refresh();
        });
    }

    private function validateAssignmentShape(array $data): void
    {
        $driver = ! empty($data['driver_id']);
        $vehicle = ! empty($data['vehicle_id']);
        $type = $data['assignment_type'];
        $valid = match ($type) {
            'driver' => $driver && ! $vehicle, 'vehicle' => ! $driver && $vehicle, 'driver_vehicle' => $driver && $vehicle, 'organization', 'shared_pool' => ! $driver && ! $vehicle, 'temporary' => $driver || $vehicle, default => false
        };
        if (! $valid) {
            throw ValidationException::withMessages(['assignment_type' => ['Assignment targets do not match the selected assignment type.']]);
        }
    }

    private function assertOwned(FuelCard $card, int $organizationId): void
    {
        if ((int) $card->owner_organization_id !== $organizationId) {
            abort(404);
        }
    }

    private function event(FuelCard $card, string $type, int $organizationId, User $actor, ?string $reason, ?array $before, ?array $after, ?FuelCardAssignment $assignment = null, ?FuelCardSettlementPolicy $policy = null): void
    {
        FuelCardEvent::query()->create(['fuel_card_id' => $card->getKey(), 'fuel_card_assignment_id' => $assignment?->getKey(), 'fuel_card_settlement_policy_id' => $policy?->getKey(), 'organization_id' => $organizationId, 'event_type' => $type, 'acted_by_user_id' => (int) $actor->getAuthIdentifier(), 'reason' => $reason, 'before_payload' => $before, 'after_payload' => $after, 'created_at' => now()]);
    }
}
