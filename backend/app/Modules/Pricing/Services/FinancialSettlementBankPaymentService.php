<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\BankTransactionEvidenceCapacityService;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentEvent;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementBankPaymentService
{
    public function __construct(private readonly BankTransactionEvidenceCapacityService $capacity) {}

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function materialize(string $statementPublicId, string $candidatePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'statement' => $statementPublicId,
            'candidate' => $candidatePublicId,
            'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($statementPublicId, $candidatePublicId, $data, $organizationId, $actor, $fingerprint): array {
            $key = (string) $data['idempotency_key'];
            $existing = FinancialSettlementBankPayment::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $key)
                ->first();
            if ($existing instanceof FinancialSettlementBankPayment) {
                if (! hash_equals((string) $existing->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            $statement = FinancialSettlementStatement::query()
                ->where('public_id', $statementPublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            $candidate = FinancialSettlementBankMatchCandidate::query()
                ->where('public_id', $candidatePublicId)
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_statement_id', $statement->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($candidate->status !== FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['candidate' => ['Only an accepted bank-match candidate may be materialized.']]);
            }
            if ((int) $candidate->revision !== (int) $data['expected_candidate_revision']) {
                throw ValidationException::withMessages(['expected_candidate_revision' => ['The bank-match candidate revision is stale.']]);
            }
            if (FinancialSettlementBankPayment::query()->where('financial_settlement_bank_match_candidate_id', $candidate->id)->exists()) {
                throw ValidationException::withMessages(['candidate' => ['This bank-match candidate has already been materialized.']]);
            }
            if ($statement->status !== FinancialSettlementStatement::STATUS_CLOSED
                || $statement->billing_document_id === null
                || (int) $statement->billing_document_id !== (int) $candidate->billing_document_id
                || $statement->output_materialized_at === null) {
                throw ValidationException::withMessages(['statement' => ['The settlement statement is no longer eligible for payment materialization.']]);
            }

            $document = BillingDocument::query()->whereKey($candidate->billing_document_id)->lockForUpdate()->firstOrFail();
            $evidence = BankTransactionEvidence::query()
                ->whereKey($candidate->bank_transaction_evidence_id)
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            if ($evidence->status !== 'recorded' || (int) $evidence->revision !== (int) $candidate->bank_transaction_evidence_revision) {
                throw ValidationException::withMessages(['candidate' => ['The candidate bank evidence is stale or unavailable.']]);
            }
            if ($evidence->direction !== $candidate->expected_bank_direction || $evidence->currency !== $candidate->currency || $document->currency !== $candidate->currency) {
                throw ValidationException::withMessages(['candidate' => ['The candidate direction or currency no longer matches its sources.']]);
            }
            $amountMinor = (int) $candidate->proposed_amount_minor;
            $settlementTotalMinor = abs((int) $statement->net_balance_minor);
            $settlementPaidMinor = (int) FinancialSettlementBankPayment::query()
                ->where('financial_settlement_statement_id', $statement->id)
                ->where('status', FinancialSettlementBankPayment::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            $settlementUnpaidMinor = max(0, $settlementTotalMinor - $settlementPaidMinor);
            if ($amountMinor <= 0 || $amountMinor > $settlementUnpaidMinor) {
                throw ValidationException::withMessages(['candidate' => ['The accepted candidate exceeds the current unpaid settlement balance.']]);
            }
            if ($this->capacity->remainingMinor($evidence) < $amountMinor) {
                throw ValidationException::withMessages(['candidate' => ['The bank transaction evidence no longer has sufficient shared capacity.']]);
            }

            $payment = FinancialSettlementBankPayment::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'financial_settlement_bank_match_candidate_id' => $candidate->id,
                'financial_settlement_statement_id' => $statement->id,
                'billing_document_id' => $document->id,
                'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint,
                'allocated_amount_minor' => $amountMinor,
                'currency' => $candidate->currency,
                'status' => FinancialSettlementBankPayment::STATUS_ACTIVE,
                'reason' => trim((string) $data['reason']),
                'allocated_by_user_id' => $actor->id,
                'allocated_at' => now(),
                'revision' => 1,
            ]);
            FinancialSettlementBankPaymentEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_settlement_bank_payment_id' => $payment->id,
                'revision' => 1,
                'event_type' => 'payment_allocated',
                'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint,
                'reason' => $payment->reason,
                'evidence' => [
                    'financial_settlement_statement_public_id' => $statement->public_id,
                    'financial_settlement_bank_match_candidate_public_id' => $candidate->public_id,
                    'billing_document_public_id' => $document->public_id,
                    'bank_transaction_evidence_public_id' => $evidence->public_id,
                    'allocated_amount_minor' => $amountMinor,
                    'settlement_paid_amount_minor_before' => $settlementPaidMinor,
                    'settlement_unpaid_amount_minor_before' => $settlementUnpaidMinor,
                    'currency' => $candidate->currency,
                    'bank_matching_performed' => true,
                    'billing_document_modified' => false,
                    'settlement_statement_modified' => false,
                ],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($payment), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function reverse(string $statementPublicId, string $paymentPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'statement' => $statementPublicId,
            'payment' => $paymentPublicId,
            'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($statementPublicId, $paymentPublicId, $data, $organizationId, $actor, $fingerprint): array {
            $statement = FinancialSettlementStatement::query()
                ->where('public_id', $statementPublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            $payment = FinancialSettlementBankPayment::query()
                ->where('public_id', $paymentPublicId)
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_statement_id', $statement->id)
                ->lockForUpdate()
                ->firstOrFail();
            $key = (string) $data['idempotency_key'];
            $event = FinancialSettlementBankPaymentEvent::query()
                ->where('financial_settlement_bank_payment_id', $payment->id)
                ->where('idempotency_key', $key)
                ->first();
            if ($event instanceof FinancialSettlementBankPaymentEvent) {
                if (! hash_equals((string) $event->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($payment), 'replayed' => true];
            }
            if ($payment->status !== FinancialSettlementBankPayment::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['payment' => ['Only an active settlement payment may be reversed.']]);
            }
            if ((int) $payment->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The settlement payment revision is stale.']]);
            }
            $evidence = BankTransactionEvidence::query()->whereKey($payment->bank_transaction_evidence_id)->lockForUpdate()->firstOrFail();
            $reason = trim((string) $data['reason']);
            $payment->forceFill([
                'status' => FinancialSettlementBankPayment::STATUS_REVERSED,
                'reversed_by_user_id' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'revision' => (int) $payment->revision + 1,
            ])->save();
            FinancialSettlementBankPaymentEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_settlement_bank_payment_id' => $payment->id,
                'revision' => (int) $payment->revision,
                'event_type' => 'payment_reversed',
                'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint,
                'reason' => $reason,
                'evidence' => [
                    'released_amount_minor' => (int) $payment->allocated_amount_minor,
                    'bank_transaction_evidence_public_id' => $evidence->public_id,
                    'bank_matching_performed' => false,
                    'billing_document_modified' => false,
                    'settlement_statement_modified' => false,
                ],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($payment->fresh()), 'replayed' => false];
        });
    }

    /** @return array<string, mixed> */
    private function present(FinancialSettlementBankPayment $payment): array
    {
        $payment->loadMissing(['candidate', 'statement', 'billingDocument', 'bankTransactionEvidence', 'events']);
        $evidence = $payment->bankTransactionEvidence;
        $candidate = $payment->candidate;
        $statement = $payment->statement;
        $document = $payment->billingDocument;
        if (! $evidence instanceof BankTransactionEvidence
            || ! $candidate instanceof FinancialSettlementBankMatchCandidate
            || ! $statement instanceof FinancialSettlementStatement
            || ! $document instanceof BillingDocument) {
            throw new \LogicException('Payment allocation relations are incomplete.');
        }

        $settlementTotalMinor = abs((int) $statement->net_balance_minor);
        $settlementPaidMinor = (int) FinancialSettlementBankPayment::query()
            ->where('financial_settlement_statement_id', $statement->id)
            ->where('status', FinancialSettlementBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');
        $settlementUnpaidMinor = max(0, $settlementTotalMinor - $settlementPaidMinor);
        $settlementPaymentState = match (true) {
            $settlementPaidMinor === 0 => 'unpaid',
            $settlementUnpaidMinor === 0 => 'paid',
            default => 'partially_paid',
        };

        return [
            'public_id' => $payment->public_id,
            'financial_settlement_statement_public_id' => $statement->public_id,
            'financial_settlement_bank_match_candidate_public_id' => $candidate->public_id,
            'billing_document_public_id' => $document->public_id,
            'bank_transaction_evidence_public_id' => $evidence->public_id,
            'allocated_amount_minor' => (int) $payment->allocated_amount_minor,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'revision' => (int) $payment->revision,
            'settlement_payment_state' => $settlementPaymentState,
            'settlement_total_amount_minor' => $settlementTotalMinor,
            'settlement_paid_amount_minor' => $settlementPaidMinor,
            'settlement_unpaid_amount_minor' => $settlementUnpaidMinor,
            'bank_transaction_allocated_amount_minor' => $this->capacity->allocatedMinor($evidence),
            'bank_transaction_unallocated_amount_minor' => $this->capacity->remainingMinor($evidence),
            'events' => $payment->events->toArray(),
            'bank_matching_performed' => $payment->status === FinancialSettlementBankPayment::STATUS_ACTIVE,
            'payment_marked' => $payment->status === FinancialSettlementBankPayment::STATUS_ACTIVE,
            'billing_document_modified' => false,
            'settlement_statement_modified' => false,
        ];
    }
}
