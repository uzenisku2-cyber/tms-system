<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankMatchCandidate;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankMatchCandidateEvent;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierFuelInvoiceBankMatchCandidateService
{
    /** @return list<array<string, mixed>> */
    public function index(string $invoicePublicId, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.view') || $actor->can('compensation.manage'), 403);
        [, $document] = $this->lockInvoice($invoicePublicId, $organizationId);

        return SupplierFuelInvoiceBankMatchCandidate::query()
            ->where('owner_organization_id', $organizationId)
            ->where('billing_document_id', $document->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (SupplierFuelInvoiceBankMatchCandidate $candidate): array => $this->present($candidate))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function propose(string $invoicePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $commandFingerprint = hash('sha256', json_encode([
            'invoice' => $invoicePublicId, 'organization_id' => $organizationId,
            'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $data, $organizationId, $actor, $commandFingerprint): array {
            $key = (string) $data['idempotency_key'];
            $existing = SupplierFuelInvoiceBankMatchCandidate::query()
                ->where('owner_organization_id', $organizationId)->where('idempotency_key', $key)->first();
            if ($existing instanceof SupplierFuelInvoiceBankMatchCandidate) {
                if (! hash_equals((string) ($existing->source_snapshot['command_fingerprint'] ?? ''), $commandFingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different proposal command.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            [$identity, $document] = $this->lockInvoice($invoicePublicId, $organizationId);
            $invoiceMinor = $this->minor((string) $document->gross_amount);
            $invoicePaid = (int) SupplierFuelInvoiceBankPayment::query()
                ->where('billing_document_id', $document->id)
                ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            $invoiceUnpaid = $invoiceMinor - $invoicePaid;
            if ($invoiceUnpaid <= 0) {
                throw ValidationException::withMessages(['supplier_fuel_invoice' => ['A fully paid supplier fuel invoice has no bank match candidate.']]);
            }

            $minimumScore = (int) ($data['minimum_score_basis_points'] ?? 6000);
            $dateWindow = (int) ($data['date_window_days'] ?? 7);
            /** @var array{evidence: BankTransactionEvidence, bank_amount_minor: int, bank_available_minor: int, score_basis_points: int, proposed_amount_minor: int, match_reasons: array<string, bool|int|float>}|null $best */
            $best = null;
            $evidenceRecords = BankTransactionEvidence::query()
                ->where('organization_context_id', $organizationId)
                ->where('status', 'recorded')->where('direction', 'debit')
                ->where('currency', $document->currency)
                ->orderByDesc('booked_at')->orderBy('id')->lockForUpdate()->get();

            foreach ($evidenceRecords as $evidence) {
                $bankMinor = $this->minor((string) $evidence->amount);
                $bankAllocated = (int) SupplierFuelInvoiceBankPayment::query()
                    ->where('bank_transaction_evidence_id', $evidence->id)
                    ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
                    ->sum('allocated_amount_minor');
                $bankAvailable = $bankMinor - $bankAllocated;
                if ($bankAvailable <= 0) {
                    continue;
                }

                $amountExact = $bankAvailable === $invoiceUnpaid;
                $variableSymbolExact = $this->sameNonEmpty($evidence->variable_symbol, $identity->variable_symbol);
                $accountExact = $this->sameNonEmpty($evidence->counterparty_account_identifier, $identity->counterparty_account_identifier);
                $supplierExact = $this->normalize((string) $evidence->counterparty_name) === $this->normalize((string) $identity->counterparty_name)
                    && $this->normalize((string) $identity->counterparty_name) !== '';
                $dateDistance = CarbonImmutable::parse((string) $evidence->booked_at)
                    ->diffInDays(CarbonImmutable::parse((string) $identity->due_on));
                $dateWithinWindow = $dateDistance <= $dateWindow;
                $score = ($amountExact ? 4000 : 0) + ($variableSymbolExact ? 3000 : 0)
                    + ($accountExact ? 1500 : 0) + ($supplierExact ? 1000 : 0) + ($dateWithinWindow ? 500 : 0);
                if ($score < $minimumScore) {
                    continue;
                }
                $proposal = [
                    'evidence' => $evidence, 'bank_amount_minor' => $bankMinor,
                    'bank_available_minor' => $bankAvailable, 'score_basis_points' => $score,
                    'proposed_amount_minor' => min($bankAvailable, $invoiceUnpaid),
                    'match_reasons' => [
                        'amount_exact' => $amountExact, 'variable_symbol_exact' => $variableSymbolExact,
                        'counterparty_account_exact' => $accountExact, 'supplier_name_exact' => $supplierExact,
                        'date_within_window' => $dateWithinWindow, 'date_distance_days' => $dateDistance,
                    ],
                ];
                if ($best === null || $score > $best['score_basis_points']
                    || ($score === $best['score_basis_points'] && (int) $evidence->id < (int) $best['evidence']->id)) {
                    $best = $proposal;
                }
            }

            if ($best === null) {
                throw ValidationException::withMessages(['bank_match_candidate' => ['No eligible bank transaction reached the minimum matching score.']]);
            }

            /** @var BankTransactionEvidence $evidence */
            $evidence = $best['evidence'];
            $candidateFingerprint = hash('sha256', json_encode([
                'organization_id' => $organizationId, 'billing_document_id' => $document->id,
                'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'invoice_unpaid_amount_minor' => $invoiceUnpaid,
                'bank_available_amount_minor' => $best['bank_available_minor'],
                'proposed_amount_minor' => $best['proposed_amount_minor'],
                'score_basis_points' => $best['score_basis_points'],
            ], JSON_THROW_ON_ERROR));
            $duplicate = SupplierFuelInvoiceBankMatchCandidate::query()
                ->where('owner_organization_id', $organizationId)
                ->where('candidate_fingerprint', $candidateFingerprint)->first();
            if ($duplicate instanceof SupplierFuelInvoiceBankMatchCandidate) {
                return ['data' => $this->present($duplicate), 'replayed' => true];
            }

            $sourceSnapshot = [
                'command_fingerprint' => $commandFingerprint,
                'supplier_fuel_invoice_public_id' => $identity->public_id,
                'document_number' => $identity->document_number,
                'invoice_variable_symbol' => $identity->variable_symbol,
                'invoice_counterparty_name' => $identity->counterparty_name,
                'invoice_counterparty_account_identifier' => $identity->counterparty_account_identifier,
                'invoice_due_on' => (string) $identity->due_on,
                'bank_transaction_evidence_public_id' => $evidence->public_id,
                'bank_source_reference' => $evidence->source_reference,
                'bank_variable_symbol' => $evidence->variable_symbol,
                'bank_counterparty_name' => $evidence->counterparty_name,
                'bank_counterparty_account_identifier' => $evidence->counterparty_account_identifier,
                'bank_booked_at' => (string) $evidence->booked_at,
                'minimum_score_basis_points' => $minimumScore, 'date_window_days' => $dateWindow,
                'payment_created' => false, 'payment_marked' => false, 'matching_execution_created' => false,
            ];
            $candidate = SupplierFuelInvoiceBankMatchCandidate::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'billing_document_id' => $document->id, 'bank_transaction_evidence_id' => $evidence->id,
                'bank_transaction_evidence_revision' => (int) $evidence->revision,
                'idempotency_key' => $key, 'candidate_fingerprint' => $candidateFingerprint,
                'currency' => $document->currency, 'bank_amount_minor' => $best['bank_amount_minor'],
                'invoice_unpaid_amount_minor' => $invoiceUnpaid,
                'proposed_amount_minor' => $best['proposed_amount_minor'],
                'score_basis_points' => $best['score_basis_points'],
                'status' => SupplierFuelInvoiceBankMatchCandidate::STATUS_PROPOSED,
                'match_reasons' => $best['match_reasons'], 'source_snapshot' => $sourceSnapshot,
                'revision' => 1, 'proposed_by_user_id' => $actor->id, 'proposed_at' => now(),
            ]);
            SupplierFuelInvoiceBankMatchCandidateEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'supplier_fuel_invoice_bank_match_candidate_id' => $candidate->id,
                'revision' => 1, 'event_type' => 'candidate_proposed', 'idempotency_key' => $key,
                'candidate_fingerprint' => $candidateFingerprint, 'reason' => trim((string) $data['reason']),
                'evidence' => ['match_reasons' => $best['match_reasons'], 'source_snapshot' => $sourceSnapshot],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($candidate), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function review(string $invoicePublicId, string $candidatePublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'invoice' => $invoicePublicId, 'candidate' => $candidatePublicId,
            'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $candidatePublicId, $data, $organizationId, $actor, $fingerprint): array {
            [, $document] = $this->lockInvoice($invoicePublicId, $organizationId);
            $candidate = SupplierFuelInvoiceBankMatchCandidate::query()
                ->where('public_id', $candidatePublicId)->where('owner_organization_id', $organizationId)
                ->where('billing_document_id', $document->id)->lockForUpdate()->firstOrFail();
            $key = (string) $data['idempotency_key'];
            $event = SupplierFuelInvoiceBankMatchCandidateEvent::query()
                ->where('supplier_fuel_invoice_bank_match_candidate_id', $candidate->id)
                ->where('idempotency_key', $key)->first();
            if ($event instanceof SupplierFuelInvoiceBankMatchCandidateEvent) {
                if (! hash_equals((string) ($event->evidence['review_command_fingerprint'] ?? ''), $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different review command.']]);
                }

                return ['data' => $this->present($candidate), 'replayed' => true];
            }
            if ($candidate->status !== SupplierFuelInvoiceBankMatchCandidate::STATUS_PROPOSED) {
                throw ValidationException::withMessages(['candidate' => ['Only a proposed bank match candidate may be reviewed.']]);
            }
            if ((int) $candidate->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The bank match candidate revision is stale.']]);
            }

            $decision = (string) $data['decision'];
            $reason = trim((string) $data['reason']);
            $candidate->forceFill([
                'status' => $decision, 'revision' => 2, 'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(), 'review_reason' => $reason,
            ])->save();
            SupplierFuelInvoiceBankMatchCandidateEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'supplier_fuel_invoice_bank_match_candidate_id' => $candidate->id,
                'revision' => 2, 'event_type' => 'candidate_'.$decision,
                'idempotency_key' => $key, 'candidate_fingerprint' => $candidate->candidate_fingerprint,
                'reason' => $reason,
                'evidence' => [
                    'review_command_fingerprint' => $fingerprint, 'decision' => $decision,
                    'proposed_amount_minor' => (int) $candidate->proposed_amount_minor,
                    'score_basis_points' => (int) $candidate->score_basis_points,
                    'payment_created' => false, 'payment_marked' => false,
                    'matching_execution_created' => false, 'fuel_rebilling_mutated' => false,
                ],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($candidate->fresh()), 'replayed' => false];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool} */
    public function materialize(
        string $invoicePublicId,
        string $candidatePublicId,
        array $data,
        int $organizationId,
        User $actor,
        SupplierFuelInvoiceBankPaymentService $paymentService,
    ): array {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = hash('sha256', json_encode([
            'invoice' => $invoicePublicId, 'candidate' => $candidatePublicId,
            'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $candidatePublicId, $data, $organizationId, $actor, $paymentService, $fingerprint): array {
            [, $document] = $this->lockInvoice($invoicePublicId, $organizationId);
            $candidate = SupplierFuelInvoiceBankMatchCandidate::query()
                ->where('public_id', $candidatePublicId)->where('owner_organization_id', $organizationId)
                ->where('billing_document_id', $document->id)->lockForUpdate()->firstOrFail();
            $key = (string) $data['idempotency_key'];
            $existingEvent = SupplierFuelInvoiceBankMatchCandidateEvent::query()
                ->where('supplier_fuel_invoice_bank_match_candidate_id', $candidate->id)
                ->where('idempotency_key', $key)->first();
            if ($existingEvent instanceof SupplierFuelInvoiceBankMatchCandidateEvent) {
                if (! hash_equals((string) ($existingEvent->evidence['materialization_command_fingerprint'] ?? ''), $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different materialization command.']]);
                }

                return ['data' => $this->present($candidate), 'replayed' => true];
            }
            if ($candidate->status !== SupplierFuelInvoiceBankMatchCandidate::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['candidate' => ['Only an accepted bank match candidate may be materialized.']]);
            }
            if ((int) $candidate->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The bank match candidate revision is stale.']]);
            }
            if ($candidate->supplier_fuel_invoice_bank_payment_id !== null) {
                throw ValidationException::withMessages(['candidate' => ['The bank match candidate was already materialized.']]);
            }
            $evidence = BankTransactionEvidence::query()
                ->whereKey($candidate->bank_transaction_evidence_id)
                ->where('organization_context_id', $organizationId)->lockForUpdate()->firstOrFail();
            $paymentResult = $paymentService->store($invoicePublicId, [
                'idempotency_key' => $key,
                'bank_transaction_evidence_public_id' => (string) $evidence->public_id,
                'expected_bank_transaction_evidence_revision' => (int) $candidate->bank_transaction_evidence_revision,
                'allocated_amount' => $this->amount((int) $candidate->proposed_amount_minor),
                'reason' => trim((string) $data['reason']),
            ], $organizationId, $actor);
            $payment = SupplierFuelInvoiceBankPayment::query()
                ->where('public_id', (string) $paymentResult['data']['public_id'])
                ->where('owner_organization_id', $organizationId)->firstOrFail();
            $reason = trim((string) $data['reason']);
            $candidate->forceFill([
                'supplier_fuel_invoice_bank_payment_id' => $payment->id,
                'materialized_by_user_id' => $actor->id, 'materialized_at' => now(), 'revision' => 3,
            ])->save();
            SupplierFuelInvoiceBankMatchCandidateEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'supplier_fuel_invoice_bank_match_candidate_id' => $candidate->id,
                'revision' => 3, 'event_type' => 'candidate_materialized',
                'idempotency_key' => $key, 'candidate_fingerprint' => $candidate->candidate_fingerprint,
                'reason' => $reason,
                'evidence' => [
                    'materialization_command_fingerprint' => $fingerprint,
                    'supplier_fuel_invoice_bank_payment_public_id' => $payment->public_id,
                    'allocated_amount_minor' => (int) $payment->allocated_amount_minor,
                    'bank_matching_performed' => true, 'fuel_settlement_mutated' => false,
                ],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($candidate->fresh()), 'replayed' => false];
        });
    }

    /** @return array{0: BillingDocumentCommercialIdentity, 1: BillingDocument} */
    private function lockInvoice(string $publicId, int $organizationId): array
    {
        $identity = BillingDocumentCommercialIdentity::query()
            ->where('public_id', $publicId)->where('owner_organization_id', $organizationId)
            ->lockForUpdate()->firstOrFail();
        $document = BillingDocument::query()->whereKey($identity->billing_document_id)->lockForUpdate()->firstOrFail();
        if ($document->document_type !== BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE
            || $identity->direction !== BillingDocumentCommercialIdentity::DIRECTION_PAYABLE) {
            throw ValidationException::withMessages(['supplier_fuel_invoice' => ['The selected document is not a payable supplier fuel invoice.']]);
        }

        return [$identity, $document];
    }

    private function sameNonEmpty(mixed $left, mixed $right): bool
    {
        $left = trim((string) $left);
        $right = trim((string) $right);

        return $left !== '' && hash_equals($left, $right);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Money amount is not an exact two-decimal value.');
        }

        return ((int) $parts[1] * 100) + (int) $parts[2];
    }

    private function amount(int $minor): string
    {
        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function present(SupplierFuelInvoiceBankMatchCandidate $candidate): array
    {
        $candidate->loadMissing(['billingDocument.commercialIdentity', 'bankTransactionEvidence', 'payment', 'events']);
        $document = $candidate->billingDocument;
        $evidence = $candidate->bankTransactionEvidence;
        $payment = $candidate->payment;
        if (! $document instanceof BillingDocument || ! $evidence instanceof BankTransactionEvidence) {
            throw new \LogicException('Bank match candidate relations are incomplete.');
        }
        if ($payment !== null && ! $payment instanceof SupplierFuelInvoiceBankPayment) {
            throw new \LogicException('Bank match candidate payment relation is invalid.');
        }
        $invoiceMinor = $this->minor((string) $document->gross_amount);
        $paidMinor = (int) SupplierFuelInvoiceBankPayment::query()
            ->where('billing_document_id', $document->id)
            ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');

        return [
            'public_id' => $candidate->public_id,
            'supplier_fuel_invoice_public_id' => $document->commercialIdentity?->public_id,
            'bank_transaction_evidence_public_id' => $evidence->public_id,
            'bank_transaction_evidence_revision' => (int) $candidate->bank_transaction_evidence_revision,
            'currency' => $candidate->currency,
            'bank_amount_minor' => (int) $candidate->bank_amount_minor,
            'invoice_unpaid_amount_minor' => (int) $candidate->invoice_unpaid_amount_minor,
            'proposed_amount_minor' => (int) $candidate->proposed_amount_minor,
            'score_basis_points' => (int) $candidate->score_basis_points,
            'status' => $candidate->status, 'revision' => (int) $candidate->revision,
            'match_reasons' => $candidate->match_reasons, 'source_snapshot' => $candidate->source_snapshot,
            'events' => $candidate->events->toArray(),
            'supplier_fuel_invoice_bank_payment_public_id' => $payment?->public_id,
            'payment_created' => $payment !== null,
            'payment_marked' => $payment !== null && $paidMinor === $invoiceMinor,
            'matching_execution_created' => false, 'fuel_rebilling_mutated' => false,
        ];
    }
}
