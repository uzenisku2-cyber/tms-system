<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingExecution;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class BankTransactionEvidenceAdministrationReadService
{
    public function __construct(private readonly BankTransactionEvidenceCapacityService $capacity) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function index(array $filters, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $query = $this->visible($organizationId);
        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (isset($filters['currency'])) {
            $query->where('currency', strtoupper((string) $filters['currency']));
        }
        if (isset($filters['booked_from'])) {
            $query->whereDate('booked_at', '>=', $filters['booked_from']);
        }
        if (isset($filters['booked_until'])) {
            $query->whereDate('booked_at', '<=', $filters['booked_until']);
        }
        if (isset($filters['reference'])) {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['reference']).'%';
            $query->where(function (Builder $references) use ($needle): void {
                $references->where('source_reference', 'like', $needle)
                    ->orWhere('bank_statement_reference', 'like', $needle)
                    ->orWhere('variable_symbol', 'like', $needle);
            });
        }
        if (isset($filters['counterparty'])) {
            $query->where('counterparty_name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['counterparty']).'%');
        }

        $items = $query->orderByDesc('booked_at')->orderByDesc('id')->get()
            ->map(fn (BankTransactionEvidence $evidence): array => $this->present($evidence, false));
        if (isset($filters['capacity'])) {
            $items = $items->filter(static fn (array $item): bool => $item['capacity_state'] === $filters['capacity'])->values();
        }
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = $items->count();

        return [
            'items' => $items->forPage($page, $perPage)->values()->all(),
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'read_only' => true,
            'accounting_posting_performed' => false,
        ];
    }

    /** @return array<string, mixed> */
    public function show(string $publicId, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $evidence = $this->visible($organizationId)
            ->with(['events', 'amountBreakdowns'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        return $this->present($evidence, true);
    }

    /** @return Builder<BankTransactionEvidence> */
    private function visible(int $organizationId): Builder
    {
        return BankTransactionEvidence::query()->where('organization_context_id', $organizationId);
    }

    /** @return array<string, mixed> */
    private function present(BankTransactionEvidence $evidence, bool $detail): array
    {
        $totalMinor = $this->capacity->minor((string) $evidence->getAttribute('amount'));
        $allocatedMinor = $this->capacity->allocatedMinor($evidence);
        $remainingMinor = $totalMinor - $allocatedMinor;
        $capacityState = $allocatedMinor <= 0 ? 'available'
            : ($remainingMinor > 0 ? 'partially_allocated' : ($remainingMinor === 0 ? 'fully_allocated' : 'over_allocated'));
        $base = [
            'public_id' => (string) $evidence->getAttribute('public_id'),
            'source_type' => (string) $evidence->getAttribute('source_type'),
            'source_reference' => (string) $evidence->getAttribute('source_reference'),
            'bank_statement_reference' => $evidence->getAttribute('bank_statement_reference'),
            'direction' => (string) $evidence->getAttribute('direction'),
            'booked_at' => (string) $evidence->getRawOriginal('booked_at'),
            'value_date' => (string) $evidence->getRawOriginal('value_date'),
            'amount_minor' => $totalMinor,
            'allocated_amount_minor' => $allocatedMinor,
            'remaining_capacity_amount_minor' => $remainingMinor,
            'capacity_state' => $capacityState,
            'currency' => (string) $evidence->getAttribute('currency'),
            'account_identifier' => $evidence->getAttribute('account_identifier'),
            'counterparty_name' => $evidence->getAttribute('counterparty_name'),
            'counterparty_account_identifier' => $evidence->getAttribute('counterparty_account_identifier'),
            'variable_symbol' => $evidence->getAttribute('variable_symbol'),
            'message' => $evidence->getAttribute('message'),
            'evidence_note' => (string) $evidence->getAttribute('evidence_note'),
            'status' => (string) $evidence->getAttribute('status'),
            'revision' => (int) $evidence->getAttribute('revision'),
            'recorded_at' => (string) $evidence->getRawOriginal('recorded_at'),
        ];
        if (! $detail) {
            return $base;
        }

        $evidenceId = (int) $evidence->getKey();
        $settlementPayments = FinancialSettlementBankPayment::query()
            ->where('bank_transaction_evidence_id', $evidenceId)->orderBy('id')->get();
        $settlementPaymentIds = $settlementPayments->modelKeys();
        $reconciliations = FinancialSettlementBankPaymentReconciliation::query()
            ->whereIn('financial_settlement_bank_payment_id', $settlementPaymentIds)->orderBy('id')->get();

        return $base + [
            'settlement_candidates' => FinancialSettlementBankMatchCandidate::query()
                ->where('bank_transaction_evidence_id', $evidenceId)->orderBy('id')->get()
                ->map(static fn (FinancialSettlementBankMatchCandidate $candidate): array => $candidate->only([
                    'public_id', 'financial_settlement_statement_id', 'proposed_amount_minor', 'currency', 'score_basis_points', 'status', 'revision',
                ]))->values()->all(),
            'settlement_payments' => $settlementPayments
                ->map(static fn (FinancialSettlementBankPayment $payment): array => $payment->only([
                    'public_id', 'financial_settlement_statement_id', 'allocated_amount_minor', 'currency', 'status', 'revision',
                ]))->values()->all(),
            'reconciliations' => $reconciliations
                ->map(static fn (FinancialSettlementBankPaymentReconciliation $reconciliation): array => $reconciliation->only([
                    'public_id', 'financial_settlement_bank_payment_id', 'status', 'reason', 'revision',
                ]))->values()->all(),
            'supplier_fuel_payments' => SupplierFuelInvoiceBankPayment::query()
                ->where('bank_transaction_evidence_id', $evidenceId)->orderBy('id')->get()
                ->map(static fn (SupplierFuelInvoiceBankPayment $payment): array => $payment->only([
                    'public_id', 'supplier_fuel_invoice_id', 'allocated_amount_minor', 'currency', 'status', 'revision',
                ]))->values()->all(),
            'vehicle_cost_matches' => VehicleCostAllocationBankMatchingExecution::query()
                ->where('bank_transaction_evidence_id', $evidenceId)->orderBy('id')->get()
                ->map(static fn (VehicleCostAllocationBankMatchingExecution $execution): array => $execution->only([
                    'public_id', 'billing_document_id', 'matched_amount', 'currency', 'status', 'revision',
                ]))->values()->all(),
            'amount_breakdowns' => $evidence->amountBreakdowns->map(static fn (Model $breakdown): array => $breakdown->toArray())->values()->all(),
            'events' => $evidence->events->map(static fn (Model $event): array => $event->only([
                'public_id', 'event_type', 'evidence', 'actor_user_id', 'revision', 'occurred_at',
            ]))->values()->all(),
            'read_only' => true,
            'payment_mutated' => false,
            'reconciliation_mutated' => false,
            'accounting_posting_performed' => false,
        ];
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('compensation.view'), 403);
    }
}
