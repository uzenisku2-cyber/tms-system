<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingExecution;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingExecutionEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingHandoff;
use App\Modules\Pricing\Models\BillingDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationBankMatchingExecutionService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function execute(string $handoffPublicId, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($handoffPublicId, $data, $organizationId, $actor): array {
            $handoff = VehicleCostAllocationBankMatchingHandoff::query()->where('public_id', $handoffPublicId)->lockForUpdate()->firstOrFail();
            if ((int) $handoff->organization_context_id !== $organizationId) {
                abort(404);
            }
            if ((int) $handoff->revision !== (int) $data['expected_handoff_revision']) {
                throw ValidationException::withMessages(['expected_handoff_revision' => ['The bank matching handoff revision has changed.']]);
            }
            if ($handoff->status !== 'prepared') {
                throw ValidationException::withMessages(['handoff' => ['Only a prepared bank matching handoff can be executed.']]);
            }
            $this->responsibleParty($handoff, $organizationId, $actor);

            $evidence = BankTransactionEvidence::query()->where('public_id', $data['bank_transaction_evidence_public_id'])->lockForUpdate()->firstOrFail();
            if ((int) $evidence->organization_context_id !== $organizationId) {
                abort(404);
            }
            if ((int) $evidence->revision !== (int) $data['expected_bank_transaction_evidence_revision']) {
                throw ValidationException::withMessages(['expected_bank_transaction_evidence_revision' => ['The bank transaction evidence revision has changed.']]);
            }
            if ($evidence->status !== 'recorded') {
                throw ValidationException::withMessages(['bank_transaction_evidence_public_id' => ['Only recorded bank transaction evidence can be matched.']]);
            }

            $byKey = VehicleCostAllocationBankMatchingExecution::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($byKey instanceof VehicleCostAllocationBankMatchingExecution) {
                if ((int) $byKey->bank_matching_handoff_id !== (int) $handoff->id || (int) $byKey->bank_transaction_evidence_id !== (int) $evidence->id) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another bank match.']]);
                }

                return $this->present($byKey);
            }

            if (VehicleCostAllocationBankMatchingExecution::query()->where('bank_matching_handoff_id', $handoff->id)->exists()) {
                throw ValidationException::withMessages(['handoff' => ['This bank matching handoff has already been executed.']]);
            }

            $billingDocument = BillingDocument::query()->whereKey($handoff->billing_document_id)->lockForUpdate()->firstOrFail();
            if ((int) $billingDocument->owner_organization_id !== $organizationId) {
                abort(404);
            }
            if ($evidence->source_reference !== $handoff->bank_transaction_reference) {
                throw ValidationException::withMessages(['bank_transaction_evidence_public_id' => ['The bank transaction source reference does not match the prepared handoff.']]);
            }
            if (strtoupper((string) $evidence->currency) !== strtoupper((string) $handoff->currency) || strtoupper((string) $billingDocument->currency) !== strtoupper((string) $handoff->currency)) {
                throw ValidationException::withMessages(['matched_amount' => ['Currency conversion requires a separate controlled workflow.']]);
            }

            $expectedDirection = $billingDocument->document_type === BillingDocument::TYPE_CUSTOMER_INVOICE ? 'credit' : 'debit';
            if ($evidence->direction !== $expectedDirection) {
                throw ValidationException::withMessages(['bank_transaction_evidence_public_id' => ['The bank transaction direction does not match the billing document.']]);
            }

            $matchedMinor = $this->minorUnits((string) $data['matched_amount']);
            $handoffMinor = $this->minorUnits((string) $handoff->evidence_amount);
            $evidenceMinor = $this->minorUnits((string) $evidence->amount);
            $documentMinor = $this->minorUnits((string) $billingDocument->gross_amount);
            $allocatedEvidenceMinor = $this->minorUnits((string) VehicleCostAllocationBankMatchingExecution::query()->where('bank_transaction_evidence_id', $evidence->id)->where('status', 'executed')->sum('matched_amount'));
            $allocatedDocumentMinor = $this->minorUnits((string) VehicleCostAllocationBankMatchingExecution::query()->where('billing_document_id', $billingDocument->id)->where('status', 'executed')->sum('matched_amount'));

            if ($matchedMinor > $handoffMinor) {
                throw ValidationException::withMessages(['matched_amount' => ['The matched amount exceeds the prepared handoff amount.']]);
            }
            if ($allocatedEvidenceMinor + $matchedMinor > $evidenceMinor) {
                throw ValidationException::withMessages(['matched_amount' => ['The matched amount exceeds the remaining bank transaction amount.']]);
            }
            if ($allocatedDocumentMinor + $matchedMinor > $documentMinor) {
                throw ValidationException::withMessages(['matched_amount' => ['The matched amount exceeds the remaining billing document amount.']]);
            }

            $execution = VehicleCostAllocationBankMatchingExecution::query()->create([
                'public_id' => (string) Str::uuid(),
                'bank_matching_handoff_id' => $handoff->id,
                'bank_transaction_evidence_id' => $evidence->id,
                'billing_document_id' => $billingDocument->id,
                'organization_context_id' => $organizationId,
                'idempotency_key' => $data['idempotency_key'],
                'handoff_revision' => (int) $handoff->revision,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'matched_amount' => $data['matched_amount'],
                'currency' => strtoupper((string) $handoff->currency),
                'effective_date' => $data['effective_date'],
                'allocation_source' => 'manual_execution',
                'status' => 'executed',
                'reason' => $data['reason'],
                'executed_by_user_id' => $actor->id,
                'executed_at' => now(),
                'revision' => 1,
            ]);

            VehicleCostAllocationBankMatchingExecutionEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'bank_matching_execution_id' => $execution->id,
                'event_type' => 'bank_transaction_match_executed',
                'evidence' => [
                    'handoff_public_id' => $handoff->public_id,
                    'bank_transaction_evidence_public_id' => $evidence->public_id,
                    'billing_document_public_id' => $billingDocument->public_id,
                    'matched_amount' => $execution->matched_amount,
                    'currency' => $execution->currency,
                    'bank_matching_performed' => true,
                    'payment_marked' => false,
                    'billing_document_modified' => false,
                    'deposit_offset_performed' => false,
                    'repair_fund_movement_performed' => false,
                ],
                'actor_user_id' => $actor->id,
                'revision' => 1,
                'occurred_at' => now(),
            ]);

            return $this->present($execution);
        });
    }

    private function authorize(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403);
        }
        $this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function responsibleParty(VehicleCostAllocationBankMatchingHandoff $handoff, int $organizationId, User $actor): void
    {
        if ($handoff->responsible_party_type === 'organization' && $handoff->responsible_organization_id) {
            $this->authorization->findManageableOrganization($actor, $organizationId, (int) $handoff->responsible_organization_id);

            return;
        }
        if ($handoff->responsible_party_type === 'driver' && $handoff->responsible_user_id) {
            $driver = Driver::query()->where('user_id', $handoff->responsible_user_id)->firstOrFail();
            $this->authorization->findVisibleDriver($actor, $organizationId, (int) $driver->id);

            return;
        }

        throw ValidationException::withMessages(['responsible_party_type' => ['A registered organization or driver is required.']]);
    }

    private function minorUnits(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function present(VehicleCostAllocationBankMatchingExecution $execution): array
    {
        $events = VehicleCostAllocationBankMatchingExecutionEvent::query()->where('bank_matching_execution_id', $execution->id)->orderBy('revision')->get();

        return [
            'execution_public_id' => $execution->public_id,
            'status' => $execution->status,
            'matched_amount' => $execution->matched_amount,
            'currency' => $execution->currency,
            'effective_date' => $execution->effective_date,
            'events' => $events->toArray(),
            'bank_matching_performed' => true,
            'payment_marked' => false,
            'billing_document_modified' => false,
            'deposit_offset_performed' => false,
            'repair_fund_movement_performed' => false,
        ];
    }
}
