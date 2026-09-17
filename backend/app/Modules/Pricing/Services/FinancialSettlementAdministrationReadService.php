<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Database\Eloquent\Builder;

final class FinancialSettlementAdministrationReadService
{
    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function mutualCharges(array $filters, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $query = $this->visibleMutualCharges($organizationId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['party_type'])) {
            $query->where('counterparty_type', $filters['party_type']);
        }
        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (isset($filters['period_from'])) {
            $query->where('service_period_until', '>=', $filters['period_from']);
        }
        if (isset($filters['period_until'])) {
            $query->where('service_period_from', '<=', $filters['period_until']);
        }
        $paginator = $query->orderByDesc('service_period_until')->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));

        return [
            'items' => $paginator->getCollection()
                ->map(fn (FinancialMutualCharge $charge): array => $this->presentCharge($charge))
                ->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function mutualCharge(string $publicId, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $charge = $this->visibleMutualCharges($organizationId)
            ->where('public_id', $publicId)->firstOrFail();

        return $this->presentCharge($charge);
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function statements(array $filters, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $query = FinancialSettlementStatement::query()->where('owner_organization_id', $organizationId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['party_type'])) {
            $query->where('recipient_type', $filters['party_type']);
        }
        if (isset($filters['direction'])) {
            $query->where('output_direction', $filters['direction']);
        }
        if (isset($filters['period_from'])) {
            $query->where('period_until', '>=', $filters['period_from']);
        }
        if (isset($filters['period_until'])) {
            $query->where('period_from', '<=', $filters['period_until']);
        }
        $paginator = $query->orderByDesc('period_until')->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));

        return [
            'items' => $paginator->getCollection()
                ->map(fn (FinancialSettlementStatement $statement): array => $this->presentStatement($statement, $actor, false))
                ->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function statement(string $publicId, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $statement = FinancialSettlementStatement::query()
            ->where('public_id', $publicId)
            ->where('owner_organization_id', $organizationId)
            ->firstOrFail();

        return $this->presentStatement($statement, $actor, true);
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('compensation.view'), 403);
    }

    /** @return Builder<FinancialMutualCharge> */
    private function visibleMutualCharges(int $organizationId): Builder
    {
        return FinancialMutualCharge::query()
            ->where(function (Builder $scope) use ($organizationId): void {
                $scope->where('owner_organization_id', $organizationId)
                    ->orWhere(function (Builder $counterparty) use ($organizationId): void {
                        $counterparty->where('counterparty_organization_id', $organizationId)
                            ->where('visibility_status', FinancialMutualCharge::VISIBILITY_SHARED);
                    });
            });
    }

    /** @return array<string, mixed> */
    private function presentCharge(FinancialMutualCharge $charge): array
    {
        $charge->loadMissing('events');

        return [
            'public_id' => $charge->public_id,
            'owner_organization_id' => (int) $charge->owner_organization_id,
            'counterparty_type' => $charge->counterparty_type,
            'counterparty_organization_id' => $charge->counterparty_organization_id === null ? null : (int) $charge->counterparty_organization_id,
            'counterparty_driver_id' => $charge->counterparty_driver_id === null ? null : (int) $charge->counterparty_driver_id,
            'direction' => $charge->direction,
            'category' => $charge->category,
            'description' => $charge->description,
            'service_period_from' => (string) $charge->getRawOriginal('service_period_from'),
            'service_period_until' => (string) $charge->getRawOriginal('service_period_until'),
            'amount_minor' => (int) $charge->amount_minor,
            'currency' => $charge->currency,
            'vat_treatment' => $charge->vat_treatment,
            'offset_eligible' => (bool) $charge->offset_eligible,
            'status' => $charge->status,
            'visibility_status' => $charge->visibility_status,
            'revision' => (int) $charge->revision,
            'source_snapshot' => $charge->source_snapshot,
            'events' => $charge->events->toArray(),
            'payment_marked' => false,
            'bank_matching_performed' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function presentStatement(FinancialSettlementStatement $statement, User $actor, bool $includeAdministration): array
    {
        $statement->loadMissing(['lines', 'events', 'billingDocument.commercialIdentity', 'billingDocument.lines']);
        $totalMinor = abs((int) $statement->net_balance_minor);
        $activePaidMinor = (int) FinancialSettlementBankPayment::query()
            ->where('owner_organization_id', $statement->owner_organization_id)
            ->where('financial_settlement_statement_id', $statement->id)
            ->where('status', FinancialSettlementBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');
        $remainingMinor = max(0, $totalMinor - $activePaidMinor);
        $paymentState = $activePaidMinor === 0 ? 'unpaid' : ($remainingMinor === 0 ? 'paid' : 'partially_paid');

        $result = [
            'public_id' => $statement->public_id,
            'recipient_type' => $statement->recipient_type,
            'recipient_organization_id' => $statement->recipient_organization_id === null ? null : (int) $statement->recipient_organization_id,
            'recipient_driver_id' => $statement->recipient_driver_id === null ? null : (int) $statement->recipient_driver_id,
            'period_from' => (string) $statement->getRawOriginal('period_from'),
            'period_until' => (string) $statement->getRawOriginal('period_until'),
            'currency' => $statement->currency,
            'status' => $statement->status,
            'earning_amount_minor' => (int) $statement->earning_amount_minor,
            'deduction_amount_minor' => (int) $statement->deduction_amount_minor,
            'net_balance_minor' => (int) $statement->net_balance_minor,
            'settlement_total_amount_minor' => $totalMinor,
            'settlement_paid_amount_minor' => $activePaidMinor,
            'settlement_remaining_amount_minor' => $remainingMinor,
            'settlement_payment_state' => $paymentState,
            'revision' => (int) $statement->revision,
            'lines' => $statement->lines->toArray(),
            'events' => $statement->events->toArray(),
            'output_kind' => $statement->output_kind,
            'output_direction' => $statement->output_direction,
            'billing_document' => $statement->billingDocument?->toArray(),
            'billing_document_created' => $statement->billing_document_id !== null,
            'payment_marked' => $paymentState === 'paid',
            'bank_matching_performed' => false,
            'can_manage_settlement_bank_payments' => $actor->can('compensation.manage'),
        ];

        if (! $includeAdministration) {
            return $result;
        }

        $payments = FinancialSettlementBankPayment::query()
            ->with('events')
            ->where('owner_organization_id', $statement->owner_organization_id)
            ->where('financial_settlement_statement_id', $statement->id)
            ->orderByDesc('id')
            ->get();
        $reconciliationsByPayment = FinancialSettlementBankPaymentReconciliation::query()
            ->with('events')
            ->where('owner_organization_id', $statement->owner_organization_id)
            ->where('financial_settlement_statement_id', $statement->id)
            ->whereIn('financial_settlement_bank_payment_id', $payments->pluck('id'))
            ->get()
            ->keyBy('financial_settlement_bank_payment_id');
        $paymentByCandidate = [];
        $presentedPayments = [];
        foreach ($payments as $payment) {
            /** @var BankTransactionEvidence|null $evidence */
            $evidence = $payment->bankTransactionEvidence()->first();
            /** @var FinancialSettlementBankPaymentReconciliation|null $reconciliation */
            $reconciliation = $reconciliationsByPayment->get((int) $payment->id);
            $paymentByCandidate[(int) $payment->financial_settlement_bank_match_candidate_id] = (string) $payment->public_id;
            $presentedPayments[] = [
                'public_id' => $payment->public_id,
                'candidate_public_id' => $payment->candidate()->value('public_id'),
                'allocated_amount_minor' => (int) $payment->allocated_amount_minor,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'reason' => $payment->reason,
                'revision' => (int) $payment->revision,
                'allocated_at' => $payment->allocated_at === null ? null : (string) $payment->getRawOriginal('allocated_at'),
                'reversed_at' => $payment->reversed_at === null ? null : (string) $payment->getRawOriginal('reversed_at'),
                'reversal_reason' => $payment->reversal_reason,
                'bank_transaction_evidence' => $evidence === null ? null : [
                    'public_id' => $evidence->public_id,
                    'source_reference' => $evidence->source_reference,
                    'booked_at' => (string) $evidence->getRawOriginal('booked_at'),
                    'direction' => $evidence->direction,
                    'amount' => $evidence->amount,
                    'currency' => $evidence->currency,
                    'counterparty_name' => $evidence->counterparty_name,
                    'variable_symbol' => $evidence->variable_symbol,
                ],
                'events' => $payment->events->toArray(),
                'reconciliation' => $reconciliation === null ? null : [
                    'public_id' => $reconciliation->public_id,
                    'status' => $reconciliation->status,
                    'payment_revision' => (int) $reconciliation->payment_revision,
                    'revision' => (int) $reconciliation->revision,
                    'reason' => $reconciliation->reason,
                    'confirmed_at' => $reconciliation->confirmed_at === null ? null : (string) $reconciliation->getRawOriginal('confirmed_at'),
                    'reopened_at' => $reconciliation->reopened_at === null ? null : (string) $reconciliation->getRawOriginal('reopened_at'),
                    'events' => $reconciliation->events->toArray(),
                ],            ];
        }

        $presentedCandidates = [];
        $candidates = FinancialSettlementBankMatchCandidate::query()
            ->with('events')
            ->where('owner_organization_id', $statement->owner_organization_id)
            ->where('financial_settlement_statement_id', $statement->id)
            ->orderByDesc('id')
            ->get();
        foreach ($candidates as $candidate) {
            /** @var BankTransactionEvidence|null $evidence */
            $evidence = $candidate->bankTransactionEvidence()->first();
            $presentedCandidates[] = [
                'public_id' => $candidate->public_id,
                'bank_transaction_evidence_public_id' => $evidence?->public_id,
                'bank_source_reference' => $evidence?->source_reference,
                'expected_bank_direction' => $candidate->expected_bank_direction,
                'bank_amount_minor' => (int) $candidate->bank_amount_minor,
                'settlement_outstanding_amount_minor' => (int) $candidate->settlement_outstanding_amount_minor,
                'proposed_amount_minor' => (int) $candidate->proposed_amount_minor,
                'currency' => $candidate->currency,
                'score_basis_points' => (int) $candidate->score_basis_points,
                'status' => $candidate->status,
                'match_reasons' => $candidate->match_reasons,
                'revision' => (int) $candidate->revision,
                'proposed_at' => $candidate->proposed_at === null ? null : (string) $candidate->getRawOriginal('proposed_at'),
                'reviewed_at' => $candidate->reviewed_at === null ? null : (string) $candidate->getRawOriginal('reviewed_at'),
                'review_reason' => $candidate->review_reason,
                'payment_public_id' => $paymentByCandidate[(int) $candidate->id] ?? null,
                'events' => $candidate->events->toArray(),
            ];
        }

        $result['bank_match_candidates'] = $presentedCandidates;
        $result['bank_payments'] = $presentedPayments;

        return $result;
    }
}
