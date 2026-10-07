<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use App\Modules\Pricing\Models\FinancialMutualChargeOffsetConsent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialMutualChargeOffsetConsentService
{
    public function index(int $organizationId, User $actor): array
    {
        $driverIds = Driver::query()->where('user_id', $actor->id)->pluck('id');
        $query = FinancialMutualCharge::query()
            ->where('visibility_status', FinancialMutualCharge::VISIBILITY_SHARED)
            ->where('status', FinancialMutualCharge::STATUS_CONFIRMED)
            ->where('direction', FinancialMutualCharge::DIRECTION_RECEIVABLE)
            ->where('offset_eligible', true)
            ->whereIn('category', [FinancialMutualCharge::CATEGORY_FUEL, FinancialMutualCharge::CATEGORY_VEHICLE_COST])
            ->where(function ($scope) use ($organizationId, $driverIds, $actor): void {
                $scope->where(function ($party) use ($organizationId, $actor): void {
                    $party->where('counterparty_type', FinancialMutualCharge::PARTY_ORGANIZATION)
                        ->where('counterparty_organization_id', $organizationId);
                    if (! $actor->can('people.manage')) {
                        $party->whereRaw('1 = 0');
                    }
                })->orWhere(function ($party) use ($organizationId, $driverIds): void {
                    $party->where('counterparty_type', FinancialMutualCharge::PARTY_DRIVER)
                        ->where('owner_organization_id', $organizationId)
                        ->whereIn('counterparty_driver_id', $driverIds);
                });
            });

        return $query->orderByDesc('id')->limit(100)->get()->map(function (FinancialMutualCharge $charge): array {
            $decision = $this->currentDecision($charge);

            return [
                'public_id' => $charge->public_id,
                'category' => $charge->category,
                'description' => $charge->description,
                'source_type' => $charge->source_type,
                'source_public_id' => $charge->source_public_id,
                'source_snapshot' => $charge->source_snapshot,
                'amount_minor' => (int) $charge->amount_minor,
                'currency' => $charge->currency,
                'service_period_from' => (string) $charge->getRawOriginal('service_period_from'),
                'service_period_until' => (string) $charge->getRawOriginal('service_period_until'),
                'revision' => (int) $charge->revision,
                'decision' => $decision?->decision,
                'decision_reason' => $decision?->reason,
            ];
        })->all();
    }

    public function decide(string $publicId, array $data, int $organizationId, User $actor): array
    {
        return DB::transaction(function () use ($publicId, $data, $organizationId, $actor): array {
            $charge = FinancialMutualCharge::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            $this->assertParty($charge, $organizationId, $actor);
            $fingerprint = hash('sha256', json_encode([
                'decision' => $data['decision'],
                'expected_revision' => (int) $data['expected_revision'],
                'reason' => trim((string) $data['reason']),
                'actor_user_id' => (int) $actor->id,
            ], JSON_THROW_ON_ERROR));
            $previous = FinancialMutualChargeOffsetConsent::query()
                ->where('financial_mutual_charge_id', $charge->id)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($previous instanceof FinancialMutualChargeOffsetConsent) {
                if (! hash_equals((string) $previous->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['This key was used for a different decision.']]);
                }

                return ['data' => $this->present($previous), 'replayed' => true];
            }
            if ((int) $charge->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The charge revision has changed.']]);
            }
            if ($charge->status !== FinancialMutualCharge::STATUS_CONFIRMED ||
                $charge->visibility_status !== FinancialMutualCharge::VISIBILITY_SHARED ||
                $charge->direction !== FinancialMutualCharge::DIRECTION_RECEIVABLE ||
                ! $charge->offset_eligible ||
                ! in_array($charge->category, [FinancialMutualCharge::CATEGORY_FUEL, FinancialMutualCharge::CATEGORY_VEHICLE_COST], true)) {
                throw ValidationException::withMessages(['financial_mutual_charge' => ['This charge is not available for offset consent.']]);
            }
            $current = $this->currentDecision($charge);
            if ($current instanceof FinancialMutualChargeOffsetConsent) {
                throw ValidationException::withMessages(['decision' => ['A decision for this charge revision already exists.']]);
            }
            $consent = FinancialMutualChargeOffsetConsent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_mutual_charge_id' => $charge->id,
                'charge_revision' => $charge->revision,
                'charge_fingerprint' => FinancialMutualChargeOffsetConsent::fingerprint($charge),
                'decision' => $data['decision'],
                'actor_user_id' => $actor->id,
                'acting_organization_id' => $organizationId,
                'idempotency_key' => $data['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'reason' => trim((string) $data['reason']),
                'decided_at' => now(),
            ]);

            return ['data' => $this->present($consent), 'replayed' => false];
        });
    }

    private function assertParty(FinancialMutualCharge $charge, int $organizationId, User $actor): void
    {
        $organizationParty = $charge->counterparty_type === FinancialMutualCharge::PARTY_ORGANIZATION &&
            (int) $charge->counterparty_organization_id === $organizationId && $actor->can('people.manage');
        $driverParty = $charge->counterparty_type === FinancialMutualCharge::PARTY_DRIVER &&
            (int) $charge->owner_organization_id === $organizationId &&
            Driver::query()->whereKey($charge->counterparty_driver_id)->where('user_id', $actor->id)->exists();
        abort_unless($organizationParty || $driverParty, 404);
    }

    public function currentDecision(FinancialMutualCharge $charge): ?FinancialMutualChargeOffsetConsent
    {
        return FinancialMutualChargeOffsetConsent::query()
            ->where('financial_mutual_charge_id', $charge->id)
            ->where('charge_revision', $charge->revision)
            ->where('charge_fingerprint', FinancialMutualChargeOffsetConsent::fingerprint($charge))
            ->latest('id')->first();
    }

    private function present(FinancialMutualChargeOffsetConsent $consent): array
    {
        return [
            'public_id' => $consent->public_id,
            'financial_mutual_charge_id' => (int) $consent->financial_mutual_charge_id,
            'charge_revision' => (int) $consent->charge_revision,
            'decision' => $consent->decision,
            'reason' => $consent->reason,
            'actor_user_id' => (int) $consent->actor_user_id,
            'decided_at' => $consent->decided_at,
        ];
    }
}
