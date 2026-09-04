<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplication;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplicationEvent;
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibility;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelTransactionSettlementApplicationService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function show(FuelTransaction $transaction, int $organizationId): array
    {
        $this->assertOwned($transaction, $organizationId);
        $application = FuelTransactionSettlementApplication::query()->where('fuel_transaction_id', $transaction->getKey())->where('owner_organization_id', $organizationId)->with('events')->first();
        if (! $application instanceof FuelTransactionSettlementApplication) {
            return ['status' => 'not_applied', 'revision' => 0, 'events' => []];
        }

        return $this->serialize($application);
    }

    public function apply(FuelTransaction $transaction, int $organizationId, User $actor, int $expectedEligibilityRevision): array
    {
        $this->assertOwned($transaction, $organizationId);

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $expectedEligibilityRevision): array {
            $locked = FuelTransaction::query()->whereKey($transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $existing = FuelTransactionSettlementApplication::query()->where('fuel_transaction_id', $locked->getKey())->lockForUpdate()->first();
            if ($existing instanceof FuelTransactionSettlementApplication) {
                throw ValidationException::withMessages(['settlement_application' => ['The fuel transaction already has a settlement application.']]);
            }
            $eligibility = FuelTransactionSettlementEligibility::query()->where('fuel_transaction_id', $locked->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->first();
            if (! $eligibility instanceof FuelTransactionSettlementEligibility || $eligibility->status !== FuelTransactionSettlementEligibility::STATUS_ELIGIBLE) {
                throw ValidationException::withMessages(['settlement_eligibility' => ['The fuel transaction is not eligible for settlement application.']]);
            }
            if ((int) $eligibility->revision !== $expectedEligibilityRevision) {
                throw ValidationException::withMessages(['expected_eligibility_revision' => ['The settlement eligibility revision is stale.']]);
            }
            $reconciliation = FuelTransactionReconciliation::query()->where('fuel_transaction_id', $locked->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->first();
            if (! $reconciliation instanceof FuelTransactionReconciliation || $reconciliation->status !== FuelTransactionReconciliation::STATUS_RESOLVED || (int) $reconciliation->revision !== (int) $eligibility->reconciliation_revision) {
                throw ValidationException::withMessages(['settlement_eligibility' => ['The settlement eligibility no longer matches the resolved reconciliation.']]);
            }
            if ($eligibility->fuel_card_settlement_policy_id === null || $eligibility->settlement_target === null || $eligibility->base_amount === null || $eligibility->currency === null) {
                throw ValidationException::withMessages(['settlement_eligibility' => ['The eligible projection is incomplete.']]);
            }
            if ($eligibility->target_driver_id !== null) {
                $this->authorization->findVisibleDriver($actor, $organizationId, (int) $eligibility->target_driver_id);
            }
            if ($eligibility->target_driver_id === null && $eligibility->target_organization_id === null) {
                throw ValidationException::withMessages(['settlement_eligibility' => ['The eligible projection has no settlement target.']]);
            }
            $appliedAt = now();
            try {
                $application = FuelTransactionSettlementApplication::query()->create([
                    'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'fuel_transaction_id' => $locked->getKey(),
                    'fuel_transaction_settlement_eligibility_id' => $eligibility->getKey(), 'eligibility_revision' => (int) $eligibility->revision,
                    'reconciliation_revision' => (int) $eligibility->reconciliation_revision, 'fuel_card_settlement_policy_id' => $eligibility->fuel_card_settlement_policy_id,
                    'settlement_target' => $eligibility->settlement_target, 'target_organization_id' => $eligibility->target_organization_id,
                    'target_driver_id' => $eligibility->target_driver_id, 'discount_beneficiary' => $eligibility->discount_beneficiary,
                    'amount_basis' => $eligibility->amount_basis, 'vat_mode' => $eligibility->vat_mode, 'applied_amount' => $eligibility->base_amount,
                    'currency' => $eligibility->currency, 'status' => FuelTransactionSettlementApplication::STATUS_APPLIED, 'revision' => 1,
                    'applied_by_user_id' => (int) $actor->getAuthIdentifier(), 'applied_at' => $appliedAt,
                ]);
            } catch (QueryException) {
                throw ValidationException::withMessages(['settlement_application' => ['The fuel transaction already has a settlement application.']]);
            }
            FuelTransactionSettlementApplicationEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'fuel_transaction_settlement_application_id' => $application->getKey(), 'revision' => 1,
                'event_type' => FuelTransactionSettlementApplicationEvent::TYPE_APPLIED, 'from_status' => null, 'to_status' => FuelTransactionSettlementApplication::STATUS_APPLIED,
                'acted_by_user_id' => (int) $actor->getAuthIdentifier(), 'metadata' => ['eligibility_public_id' => $eligibility->public_id, 'eligibility_revision' => (int) $eligibility->revision, 'reconciliation_revision' => (int) $eligibility->reconciliation_revision], 'occurred_at' => $appliedAt,
            ]);

            return $this->serialize($application->refresh()->load('events'));
        });
    }

    public function reverse(FuelTransaction $transaction, int $organizationId, User $actor, int $expectedRevision, string $reason): array
    {
        $this->assertOwned($transaction, $organizationId);
        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages(['reason' => ['A reversal reason is required.']]);
        }

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $expectedRevision, $normalizedReason): array {
            $application = FuelTransactionSettlementApplication::query()->where('fuel_transaction_id', $transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            if ((int) $application->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The settlement application revision is stale.']]);
            }
            if ($application->status !== FuelTransactionSettlementApplication::STATUS_APPLIED) {
                throw ValidationException::withMessages(['settlement_application' => ['Only an applied settlement application may be reversed.']]);
            }
            if ($application->target_driver_id !== null) {
                $this->authorization->findVisibleDriver($actor, $organizationId, (int) $application->target_driver_id);
            }
            $reversedAt = now();
            $nextRevision = $expectedRevision + 1;
            FuelTransactionSettlementApplicationEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'fuel_transaction_settlement_application_id' => $application->getKey(), 'revision' => $nextRevision,
                'event_type' => FuelTransactionSettlementApplicationEvent::TYPE_REVERSED, 'from_status' => FuelTransactionSettlementApplication::STATUS_APPLIED,
                'to_status' => FuelTransactionSettlementApplication::STATUS_REVERSED, 'acted_by_user_id' => (int) $actor->getAuthIdentifier(),
                'reason' => $normalizedReason, 'metadata' => ['eligibility_revision' => (int) $application->eligibility_revision, 'reconciliation_revision' => (int) $application->reconciliation_revision], 'occurred_at' => $reversedAt,
            ]);
            $application->forceFill(['status' => FuelTransactionSettlementApplication::STATUS_REVERSED, 'revision' => $nextRevision, 'reversed_by_user_id' => (int) $actor->getAuthIdentifier(), 'reversed_at' => $reversedAt, 'reversal_reason' => $normalizedReason])->save();

            return $this->serialize($application->refresh()->load('events'));
        });
    }

    private function serialize(FuelTransactionSettlementApplication $application): array
    {
        return [
            'public_id' => $application->public_id, 'status' => $application->status, 'revision' => (int) $application->revision,
            'eligibility_revision' => (int) $application->eligibility_revision, 'reconciliation_revision' => (int) $application->reconciliation_revision,
            'settlement_target' => $application->settlement_target, 'target_organization_id' => $application->target_organization_id === null ? null : (int) $application->target_organization_id,
            'target_driver_id' => $application->target_driver_id === null ? null : (int) $application->target_driver_id, 'discount_beneficiary' => $application->discount_beneficiary,
            'amount_basis' => $application->amount_basis, 'vat_mode' => $application->vat_mode, 'applied_amount' => $application->applied_amount, 'currency' => $application->currency,
            'financial_calculation_id' => $application->financial_calculation_id === null ? null : (int) $application->financial_calculation_id,
            'applied_at' => Carbon::parse((string) $application->applied_at)->toAtomString(), 'reversed_at' => $application->reversed_at === null ? null : Carbon::parse((string) $application->reversed_at)->toAtomString(),
            'reversal_reason' => $application->reversal_reason,
            'events' => $application->events->map(fn (FuelTransactionSettlementApplicationEvent $event): array => ['revision' => (int) $event->revision, 'event_type' => $event->event_type, 'from_status' => $event->from_status, 'to_status' => $event->to_status, 'reason' => $event->reason, 'metadata' => (array) $event->metadata])->all(),
        ];
    }

    private function assertOwned(FuelTransaction $transaction, int $organizationId): void
    {
        abort_unless((int) $transaction->owner_organization_id === $organizationId, 404);
    }
}
