<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelCardSettlementPolicy;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibility;
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibilityEvaluation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelTransactionSettlementEligibilityService
{
    private const EVALUATION_VERSION = 'v1';

    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function show(FuelTransaction $transaction, int $organizationId): array
    {
        $this->assertOwned($transaction, $organizationId);
        $eligibility = FuelTransactionSettlementEligibility::query()->where('fuel_transaction_id', $transaction->getKey())->where('owner_organization_id', $organizationId)->with('evaluations')->first();
        if (! $eligibility instanceof FuelTransactionSettlementEligibility) {
            return ['status' => 'not_evaluated', 'result_code' => null, 'revision' => 0, 'evaluations' => []];
        }

        return $this->serialize($eligibility);
    }

    public function evaluate(FuelTransaction $transaction, int $organizationId, User $actor, int $expectedRevision): array
    {
        $this->assertOwned($transaction, $organizationId);

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $expectedRevision): array {
            $locked = FuelTransaction::query()->whereKey($transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $eligibility = FuelTransactionSettlementEligibility::query()->where('fuel_transaction_id', $locked->getKey())->lockForUpdate()->first();
            if (! $eligibility instanceof FuelTransactionSettlementEligibility) {
                $eligibility = FuelTransactionSettlementEligibility::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'fuel_transaction_id' => $locked->getKey(), 'status' => FuelTransactionSettlementEligibility::STATUS_BLOCKED, 'result_code' => 'not_evaluated', 'reconciliation_revision' => 0, 'revision' => 0]);
            }
            if ((int) $eligibility->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The settlement eligibility revision is stale.']]);
            }

            $result = $this->resolve($locked, $organizationId, $actor);
            $nextRevision = $expectedRevision + 1;
            $evaluatedAt = now();
            $values = [...$result, 'revision' => $nextRevision, 'evaluated_at' => $evaluatedAt];
            FuelTransactionSettlementEligibilityEvaluation::query()->create([...$values, 'public_id' => (string) Str::uuid(), 'fuel_transaction_settlement_eligibility_id' => $eligibility->getKey(), 'evaluation_version' => self::EVALUATION_VERSION, 'evaluated_by_user_id' => (int) $actor->getAuthIdentifier()]);
            $eligibility->forceFill(collect($values)->except(['evidence'])->all())->save();

            return $this->serialize($eligibility->refresh()->load('evaluations'));
        });
    }

    private function resolve(FuelTransaction $transaction, int $organizationId, User $actor): array
    {
        $base = ['status' => FuelTransactionSettlementEligibility::STATUS_BLOCKED, 'fuel_card_settlement_policy_id' => null, 'reconciliation_revision' => 0, 'settlement_target' => null, 'target_organization_id' => null, 'target_driver_id' => null, 'discount_beneficiary' => null, 'amount_basis' => null, 'vat_mode' => null, 'base_amount' => null, 'currency' => null];
        $reconciliation = FuelTransactionReconciliation::query()->where('fuel_transaction_id', $transaction->getKey())->where('owner_organization_id', $organizationId)->first();
        if (! $reconciliation instanceof FuelTransactionReconciliation || $reconciliation->status !== FuelTransactionReconciliation::STATUS_RESOLVED) {
            return [...$base, 'result_code' => 'reconciliation_not_resolved', 'evidence' => []];
        }
        $base['reconciliation_revision'] = (int) $reconciliation->revision;
        if ($transaction->fuel_card_id === null) {
            return [...$base, 'result_code' => 'settlement_policy_missing', 'evidence' => []];
        }
        $date = Carbon::parse((string) $transaction->occurred_at)->toDateString();
        $policies = FuelCardSettlementPolicy::query()->where('owner_organization_id', $organizationId)->where('fuel_card_id', $transaction->fuel_card_id)->whereDate('valid_from', '<=', $date)->where(fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date))->orderBy('id')->get();
        if ($policies->count() !== 1) {
            return [...$base, 'result_code' => $policies->isEmpty() ? 'settlement_policy_missing' : 'settlement_policy_ambiguous', 'evidence' => ['policy_count' => $policies->count(), 'service_date' => $date]];
        }
        $policy = $policies->first();
        if (! $policy instanceof FuelCardSettlementPolicy) {
            throw new \LogicException('Settlement policy resolution failed.');
        }
        $targetOrganizationId = $policy->settlement_target === 'carrier' ? $transaction->responsible_organization_id : null;
        $targetDriverId = $policy->settlement_target === 'driver' ? $reconciliation->effective_driver_id : null;
        if ($targetDriverId !== null) {
            $this->authorization->findVisibleDriver($actor, $organizationId, (int) $targetDriverId);
        }
        if ($targetOrganizationId === null && $targetDriverId === null) {
            return [...$base, 'result_code' => 'settlement_target_missing', 'fuel_card_settlement_policy_id' => $policy->getKey(), 'evidence' => []];
        }
        $amount = $policy->amount_basis === 'net' ? $transaction->net_amount : $transaction->gross_amount;
        if ($amount === null || (float) $amount < 0) {
            return [...$base, 'result_code' => 'settlement_amount_missing', 'fuel_card_settlement_policy_id' => $policy->getKey(), 'evidence' => []];
        }

        return ['status' => FuelTransactionSettlementEligibility::STATUS_ELIGIBLE, 'result_code' => 'eligible', 'fuel_card_settlement_policy_id' => $policy->getKey(), 'reconciliation_revision' => (int) $reconciliation->revision, 'settlement_target' => $policy->settlement_target, 'target_organization_id' => $targetOrganizationId, 'target_driver_id' => $targetDriverId, 'discount_beneficiary' => $policy->discount_beneficiary, 'amount_basis' => $policy->amount_basis, 'vat_mode' => $policy->vat_mode, 'base_amount' => (string) $amount, 'currency' => $transaction->currency, 'evidence' => ['service_date' => $date, 'reconciliation_public_id' => $reconciliation->public_id, 'policy_public_id' => $policy->public_id]];
    }

    private function serialize(FuelTransactionSettlementEligibility $eligibility): array
    {
        return ['status' => $eligibility->status, 'result_code' => $eligibility->result_code, 'revision' => (int) $eligibility->revision, 'reconciliation_revision' => (int) $eligibility->reconciliation_revision, 'settlement_target' => $eligibility->settlement_target, 'target_organization_id' => $eligibility->target_organization_id === null ? null : (int) $eligibility->target_organization_id, 'target_driver_id' => $eligibility->target_driver_id === null ? null : (int) $eligibility->target_driver_id, 'discount_beneficiary' => $eligibility->discount_beneficiary, 'amount_basis' => $eligibility->amount_basis, 'vat_mode' => $eligibility->vat_mode, 'base_amount' => $eligibility->base_amount, 'currency' => $eligibility->currency, 'evaluated_at' => $eligibility->evaluated_at === null ? null : Carbon::parse((string) $eligibility->evaluated_at)->toAtomString(), 'evaluations' => $eligibility->evaluations->map(fn (FuelTransactionSettlementEligibilityEvaluation $evaluation): array => ['revision' => (int) $evaluation->revision, 'status' => $evaluation->status, 'result_code' => $evaluation->result_code, 'evidence' => (array) $evaluation->evidence])->all()];
    }

    private function assertOwned(FuelTransaction $transaction, int $organizationId): void
    {
        abort_unless((int) $transaction->owner_organization_id === $organizationId, 404);
    }
}
