<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\BankTransactionEvidenceCapacityService;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidateEvent;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementBankMatchCandidateProposalService
{
    public function __construct(private readonly BankTransactionEvidenceCapacityService $capacity) {}

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function propose(string $statementPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $data = validator($data, [
            'idempotency_key' => ['required', 'uuid'],
            'minimum_score_basis_points' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'date_window_days' => ['sometimes', 'integer', 'min:0', 'max:366'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ])->validate();
        $commandFingerprint = hash('sha256', json_encode([
            'statement' => $statementPublicId,
            'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($statementPublicId, $data, $organizationId, $actor, $commandFingerprint): array {
            $idempotencyKey = (string) $data['idempotency_key'];
            $existing = FinancialSettlementBankMatchCandidate::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing instanceof FinancialSettlementBankMatchCandidate) {
                if (! hash_equals((string) ($existing->source_snapshot['command_fingerprint'] ?? ''), $commandFingerprint)) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['The idempotency key was already used with a different proposal command.'],
                    ]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            $statement = FinancialSettlementStatement::query()
                ->where('public_id', $statementPublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            $expectedDirection = $this->expectedBankDirection($statement);
            $billingDocument = BillingDocument::query()->findOrFail($statement->billing_document_id);
            $identity = BillingDocumentCommercialIdentity::query()
                ->where('billing_document_id', $billingDocument->id)
                ->where('owner_organization_id', $organizationId)
                ->firstOrFail();
            $settlementTotalMinor = abs((int) $statement->net_balance_minor);
            $settlementPaidMinor = (int) FinancialSettlementBankPayment::query()
                ->where('financial_settlement_statement_id', $statement->id)
                ->where('status', FinancialSettlementBankPayment::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            $outstandingMinor = max(0, $settlementTotalMinor - $settlementPaidMinor);
            if ($outstandingMinor === 0) {
                throw ValidationException::withMessages([
                    'bank_match_candidate' => ['The settlement statement is already fully paid.'],
                ]);
            }
            $minimumScore = (int) ($data['minimum_score_basis_points'] ?? 6000);
            $dateWindowDays = (int) ($data['date_window_days'] ?? 7);
            if ($minimumScore < 1 || $minimumScore > 10000) {
                throw ValidationException::withMessages(['minimum_score_basis_points' => ['The minimum score must be between 1 and 10000.']]);
            }
            if ($dateWindowDays < 0 || $dateWindowDays > 366) {
                throw ValidationException::withMessages(['date_window_days' => ['The date window must be between 0 and 366 days.']]);
            }

            /** @var array{evidence: BankTransactionEvidence, bank_amount_minor: int, proposed_amount_minor: int, score_basis_points: int, match_reasons: array<string, bool|int|float>}|null $best */
            $best = null;
            $evidenceRecords = BankTransactionEvidence::query()
                ->where('organization_context_id', $organizationId)
                ->where('status', 'recorded')
                ->where('direction', $expectedDirection)
                ->where('currency', $statement->currency)
                ->orderByDesc('booked_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($evidenceRecords as $evidence) {
                $bankAmountMinor = $this->minor((string) $evidence->amount);
                $bankRemainingMinor = $this->capacity->remainingMinor($evidence);
                $proposedAmountMinor = min($outstandingMinor, $bankRemainingMinor);
                if ($proposedAmountMinor <= 0) {
                    continue;
                }
                $amountExact = $proposedAmountMinor === $outstandingMinor;
                $variableSymbolExact = $this->sameNonEmpty($evidence->variable_symbol, $identity->variable_symbol);
                $accountExact = $this->sameNonEmpty($evidence->counterparty_account_identifier, $identity->counterparty_account_identifier);
                $counterpartyExact = $this->normalize((string) $evidence->counterparty_name) === $this->normalize((string) $identity->counterparty_name)
                    && $this->normalize((string) $identity->counterparty_name) !== '';
                $dateDistance = CarbonImmutable::parse((string) $evidence->booked_at)
                    ->diffInDays(CarbonImmutable::parse((string) $identity->due_on));
                $dateWithinWindow = $dateDistance <= $dateWindowDays;
                $score = ($amountExact ? 5000 : 3500) + ($variableSymbolExact ? 2500 : 0) + ($accountExact ? 1500 : 0)
                    + ($counterpartyExact ? 500 : 0) + ($dateWithinWindow ? 500 : 0);
                if ($score < $minimumScore) {
                    continue;
                }
                $proposal = [
                    'evidence' => $evidence,
                    'bank_amount_minor' => $bankAmountMinor,
                    'proposed_amount_minor' => $proposedAmountMinor,
                    'score_basis_points' => $score,
                    'match_reasons' => [
                        'amount_exact' => $amountExact,
                        'amount_partial' => ! $amountExact,
                        'bank_remaining_capacity_minor' => $bankRemainingMinor,
                        'direction_exact' => true,
                        'currency_exact' => true,
                        'variable_symbol_exact' => $variableSymbolExact,
                        'counterparty_account_exact' => $accountExact,
                        'counterparty_name_exact' => $counterpartyExact,
                        'date_within_window' => $dateWithinWindow,
                        'date_distance_days' => $dateDistance,
                    ],
                ];
                if ($best === null || $score > $best['score_basis_points']
                    || ($score === $best['score_basis_points'] && $proposedAmountMinor > $best['proposed_amount_minor'])
                    || ($score === $best['score_basis_points'] && $proposedAmountMinor === $best['proposed_amount_minor']
                        && (int) $evidence->id < (int) $best['evidence']->id)) {
                    $best = $proposal;
                }
            }

            if ($best === null) {
                throw ValidationException::withMessages([
                    'bank_match_candidate' => ['No eligible bank transaction with available capacity reached the minimum matching score.'],
                ]);
            }

            $evidence = $best['evidence'];
            $candidateFingerprint = hash('sha256', json_encode([
                'organization_id' => $organizationId,
                'financial_settlement_statement_id' => $statement->id,
                'billing_document_id' => $statement->billing_document_id,
                'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'expected_bank_direction' => $expectedDirection,
                'settlement_outstanding_amount_minor' => $outstandingMinor,
                'bank_amount_minor' => $best['bank_amount_minor'],
                'proposed_amount_minor' => $best['proposed_amount_minor'],
                'score_basis_points' => $best['score_basis_points'],
            ], JSON_THROW_ON_ERROR));
            $duplicate = FinancialSettlementBankMatchCandidate::query()
                ->where('owner_organization_id', $organizationId)
                ->where('candidate_fingerprint', $candidateFingerprint)
                ->first();
            if ($duplicate instanceof FinancialSettlementBankMatchCandidate) {
                return ['data' => $this->present($duplicate), 'replayed' => true];
            }

            $sourceSnapshot = [
                'command_fingerprint' => $commandFingerprint,
                'financial_settlement_statement_public_id' => $statement->public_id,
                'billing_document_public_id' => $billingDocument->public_id,
                'document_number' => $identity->document_number,
                'settlement_variable_symbol' => $identity->variable_symbol,
                'settlement_counterparty_name' => $identity->counterparty_name,
                'settlement_counterparty_account_identifier' => $identity->counterparty_account_identifier,
                'settlement_due_on' => (string) $identity->due_on,
                'bank_transaction_evidence_public_id' => $evidence->public_id,
                'bank_source_reference' => $evidence->source_reference,
                'bank_variable_symbol' => $evidence->variable_symbol,
                'bank_counterparty_name' => $evidence->counterparty_name,
                'bank_counterparty_account_identifier' => $evidence->counterparty_account_identifier,
                'bank_booked_at' => (string) $evidence->booked_at,
                'minimum_score_basis_points' => $minimumScore,
                'date_window_days' => $dateWindowDays,
                'settlement_total_amount_minor' => $settlementTotalMinor,
                'settlement_paid_amount_minor' => $settlementPaidMinor,
                'settlement_unpaid_amount_minor' => $outstandingMinor,
                'payment_allocation_created' => false,
                'payment_marked' => false,
                'bank_matching_performed' => false,
            ];
            $candidate = FinancialSettlementBankMatchCandidate::query()->create([
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'financial_settlement_statement_id' => $statement->id,
                'billing_document_id' => $statement->billing_document_id,
                'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'idempotency_key' => $idempotencyKey,
                'candidate_fingerprint' => $candidateFingerprint,
                'currency' => $statement->currency,
                'expected_bank_direction' => $expectedDirection,
                'bank_amount_minor' => $best['bank_amount_minor'],
                'settlement_outstanding_amount_minor' => $outstandingMinor,
                'proposed_amount_minor' => $best['proposed_amount_minor'],
                'score_basis_points' => $best['score_basis_points'],
                'status' => FinancialSettlementBankMatchCandidate::STATUS_PROPOSED,
                'match_reasons' => $best['match_reasons'],
                'source_snapshot' => $sourceSnapshot,
                'revision' => 1,
                'proposed_by_user_id' => $actor->id,
                'proposed_at' => now(),
            ]);
            FinancialSettlementBankMatchCandidateEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_settlement_bank_match_candidate_id' => $candidate->id,
                'revision' => 1,
                'event_type' => 'candidate_proposed',
                'idempotency_key' => $idempotencyKey,
                'candidate_fingerprint' => $candidateFingerprint,
                'reason' => trim((string) $data['reason']),
                'evidence' => ['match_reasons' => $best['match_reasons'], 'source_snapshot' => $sourceSnapshot],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($candidate), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function review(
        string $statementPublicId,
        string $candidatePublicId,
        array $data,
        int $organizationId,
        User $actor,
    ): array {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'statement' => $statementPublicId, 'candidate' => $candidatePublicId,
            'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($statementPublicId, $candidatePublicId, $data, $organizationId, $actor, $fingerprint): array {
            $statement = FinancialSettlementStatement::query()
                ->where('public_id', $statementPublicId)
                ->where('owner_organization_id', $organizationId)
                ->firstOrFail();
            $candidate = FinancialSettlementBankMatchCandidate::query()
                ->where('public_id', $candidatePublicId)
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_statement_id', $statement->id)
                ->lockForUpdate()
                ->firstOrFail();
            $idempotencyKey = (string) $data['idempotency_key'];
            $event = FinancialSettlementBankMatchCandidateEvent::query()
                ->where('financial_settlement_bank_match_candidate_id', $candidate->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($event instanceof FinancialSettlementBankMatchCandidateEvent) {
                if (! hash_equals((string) ($event->evidence['review_command_fingerprint'] ?? ''), $fingerprint)) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => ['The idempotency key was already used with a different review command.'],
                    ]);
                }

                return ['data' => $this->present($candidate), 'replayed' => true];
            }
            if ($candidate->status !== FinancialSettlementBankMatchCandidate::STATUS_PROPOSED) {
                throw ValidationException::withMessages(['candidate' => ['Only a proposed bank match candidate may be reviewed.']]);
            }
            if ((int) $candidate->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The bank match candidate revision is stale.']]);
            }

            $decision = (string) $data['decision'];
            $allowed = [
                FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED,
                FinancialSettlementBankMatchCandidate::STATUS_REJECTED,
                FinancialSettlementBankMatchCandidate::STATUS_SUPERSEDED,
            ];
            if (! in_array($decision, $allowed, true)) {
                throw ValidationException::withMessages(['decision' => ['The review decision is invalid.']]);
            }
            $eventType = match ($decision) {
                FinancialSettlementBankMatchCandidate::STATUS_ACCEPTED => 'candidate_accepted',
                FinancialSettlementBankMatchCandidate::STATUS_REJECTED => 'candidate_rejected',
                FinancialSettlementBankMatchCandidate::STATUS_SUPERSEDED => 'candidate_superseded',
            };
            $reason = trim((string) $data['reason']);
            $revision = (int) $candidate->revision + 1;
            $candidate->forceFill([
                'status' => $decision, 'revision' => $revision, 'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(), 'review_reason' => $reason,
            ])->save();
            FinancialSettlementBankMatchCandidateEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_settlement_bank_match_candidate_id' => $candidate->id,
                'revision' => $revision, 'event_type' => $eventType,
                'idempotency_key' => $idempotencyKey, 'candidate_fingerprint' => $candidate->candidate_fingerprint,
                'reason' => $reason,
                'evidence' => [
                    'review_command_fingerprint' => $fingerprint, 'decision' => $decision,
                    'proposed_amount_minor' => (int) $candidate->proposed_amount_minor,
                    'score_basis_points' => (int) $candidate->score_basis_points,
                    'payment_allocation_created' => false, 'payment_marked' => false,
                    'bank_matching_performed' => false,
                ],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($candidate->fresh()), 'replayed' => false];
        });
    }

    private function expectedBankDirection(FinancialSettlementStatement $statement): string
    {
        if ($statement->status !== FinancialSettlementStatement::STATUS_CLOSED
            || $statement->recipient_type !== FinancialSettlementStatement::RECIPIENT_ORGANIZATION
            || $statement->billing_document_id === null
            || $statement->output_materialized_at === null) {
            throw ValidationException::withMessages(['statement' => ['Only a closed materialized external-carrier settlement is eligible.']]);
        }

        return match ($statement->output_kind) {
            FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE => 'debit',
            FinancialSettlementStatement::OUTPUT_CARRIER_RECEIVABLE => 'credit',
            default => throw ValidationException::withMessages([
                'statement' => ['Driver and zero-balance settlement outputs are not eligible for bank matching.'],
            ]),
        };
    }

    private function sameNonEmpty(mixed $left, mixed $right): bool
    {
        $normalizedLeft = $this->normalize((string) $left);
        $normalizedRight = $this->normalize((string) $right);

        return $normalizedLeft !== '' && $normalizedLeft === $normalizedRight;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', '', trim($value)));
    }

    private function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Bank evidence amount is not an exact two-decimal value.');
        }

        return ((int) $parts[1] * 100) + (int) $parts[2];
    }

    /** @return array<string, mixed> */
    private function present(FinancialSettlementBankMatchCandidate $candidate): array
    {
        return [
            'public_id' => $candidate->public_id,
            'financial_settlement_statement_id' => (int) $candidate->financial_settlement_statement_id,
            'billing_document_id' => (int) $candidate->billing_document_id,
            'bank_transaction_evidence_id' => (int) $candidate->bank_transaction_evidence_id,
            'bank_transaction_evidence_revision' => (int) $candidate->bank_transaction_evidence_revision,
            'currency' => $candidate->currency,
            'expected_bank_direction' => $candidate->expected_bank_direction,
            'bank_amount_minor' => (int) $candidate->bank_amount_minor,
            'settlement_outstanding_amount_minor' => (int) $candidate->settlement_outstanding_amount_minor,
            'proposed_amount_minor' => (int) $candidate->proposed_amount_minor,
            'score_basis_points' => (int) $candidate->score_basis_points,
            'status' => $candidate->status,
            'match_reasons' => $candidate->match_reasons,
            'source_snapshot' => $candidate->source_snapshot,
            'revision' => (int) $candidate->revision,
            'payment_allocation_created' => false,
            'payment_marked' => false,
            'bank_matching_performed' => false,
        ];
    }
}
