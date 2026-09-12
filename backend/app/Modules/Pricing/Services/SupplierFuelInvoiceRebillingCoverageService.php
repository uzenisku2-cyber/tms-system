<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplication;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Models\FinancialSettlementStatementLine;
use App\Modules\Pricing\Models\SupplierFuelInvoiceRebillingCoverage;
use App\Modules\Pricing\Models\SupplierFuelInvoiceRebillingCoverageEvent;
use App\Modules\Pricing\Models\SupplierFuelInvoiceRebillingCoverageLine;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierFuelInvoiceRebillingCoverageService
{
    public function evaluate(string $invoicePublicId, array $data, int $organizationId, ?User $actor): array
    {
        if (! $actor instanceof User || ! $actor->can('compensation.manage')) {
            abort(403);
        }

        $basis = (string) $data['comparison_basis'];
        $command = ['invoice_public_id' => $invoicePublicId, 'comparison_basis' => $basis];
        $fingerprint = hash('sha256', json_encode($command, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $data, $organizationId, $actor, $basis, $fingerprint): array {
            $existing = SupplierFuelInvoiceRebillingCoverage::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', (string) $data['idempotency_key'])
                ->lockForUpdate()->first();
            if ($existing instanceof SupplierFuelInvoiceRebillingCoverage) {
                if ($existing->command_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key is already used for another coverage evaluation.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            $identity = BillingDocumentCommercialIdentity::query()
                ->where('public_id', $invoicePublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()->firstOrFail();
            $document = BillingDocument::query()
                ->whereKey($identity->billing_document_id)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()->firstOrFail();
            if ($document->document_type !== BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE) {
                throw ValidationException::withMessages(['supplier_fuel_invoice' => ['The document is not a supplier fuel invoice.']]);
            }

            $allocations = SupplierFuelInvoiceTransactionAllocation::query()
                ->where('owner_organization_id', $organizationId)
                ->where('billing_document_id', $document->getKey())
                ->where('status', SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE)
                ->with('fuelTransaction')->orderBy('id')->lockForUpdate()->get();
            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages(['supplier_fuel_invoice' => ['The invoice has no active fuel transaction allocations.']]);
            }

            $rows = [];
            foreach ($allocations as $allocation) {
                $transaction = $allocation->fuelTransaction;
                if (! $transaction instanceof FuelTransaction) {
                    throw new \LogicException('Fuel invoice allocation has no fuel transaction.');
                }
                if ($allocation->currency !== $document->currency || $transaction->currency !== $document->currency) {
                    throw ValidationException::withMessages(['supplier_fuel_invoice' => ['Coverage sources must use the invoice currency.']]);
                }

                $transactionGrossMinor = $this->minor((string) $transaction->gross_amount);
                if ($transactionGrossMinor <= 0) {
                    throw ValidationException::withMessages(['supplier_fuel_invoice' => ['A covered fuel transaction must have a positive gross amount.']]);
                }
                $allocatedGrossMinor = (int) $allocation->allocated_amount_minor;
                $purchaseMinor = $basis === 'gross'
                    ? $allocatedGrossMinor
                    : $this->proportion($this->minor((string) $transaction->net_amount), $allocatedGrossMinor, $transactionGrossMinor);

                $application = FuelTransactionSettlementApplication::query()
                    ->where('owner_organization_id', $organizationId)
                    ->where('fuel_transaction_id', $transaction->getKey())
                    ->where('status', FuelTransactionSettlementApplication::STATUS_APPLIED)
                    ->where('amount_basis', $basis)
                    ->orderByDesc('id')->first();

                $statement = null;
                $statementLine = null;
                if ($application instanceof FuelTransactionSettlementApplication && $application->financial_calculation_id !== null) {
                    $statementLine = FinancialSettlementStatementLine::query()
                        ->where('financial_calculation_id', $application->financial_calculation_id)
                        ->whereHas('statement', fn ($query) => $query->where('owner_organization_id', $organizationId))
                        ->with('statement')->orderByDesc('id')->first();
                    $candidate = $statementLine?->statement;
                    if ($candidate instanceof FinancialSettlementStatement
                        && $candidate->status === FinancialSettlementStatement::STATUS_CLOSED
                        && $candidate->output_materialized_at !== null
                        && $candidate->billing_document_id !== null
                        && $this->statementMatchesApplication($candidate, $application)) {
                        $statement = $candidate;
                    }
                }

                $rebilledMinor = 0;
                if ($application instanceof FuelTransactionSettlementApplication && $statement instanceof FinancialSettlementStatement) {
                    $applicationMinor = $this->minor((string) $application->applied_amount);
                    $rebilledMinor = $this->proportion($applicationMinor, $allocatedGrossMinor, $transactionGrossMinor);
                }
                $unrebilledMinor = max($purchaseMinor - $rebilledMinor, 0);
                $marginMinor = $rebilledMinor - $purchaseMinor;
                $status = $application === null || $statement === null
                    ? SupplierFuelInvoiceRebillingCoverageLine::STATUS_MISSING
                    : ($marginMinor < 0
                        ? SupplierFuelInvoiceRebillingCoverageLine::STATUS_NEGATIVE_MARGIN
                        : SupplierFuelInvoiceRebillingCoverageLine::STATUS_COMPLETE);

                $rows[] = compact(
                    'allocation', 'transaction', 'application', 'statementLine', 'statement',
                    'purchaseMinor', 'rebilledMinor', 'unrebilledMinor', 'marginMinor', 'status',
                );
            }

            $purchaseTotal = array_sum(array_column($rows, 'purchaseMinor'));
            $rebilledTotal = array_sum(array_column($rows, 'rebilledMinor'));
            $unrebilledTotal = array_sum(array_column($rows, 'unrebilledMinor'));
            $marginTotal = $rebilledTotal - $purchaseTotal;
            $hasMissing = collect($rows)->contains(fn (array $row): bool => $row['status'] === SupplierFuelInvoiceRebillingCoverageLine::STATUS_MISSING);
            $hasNegative = collect($rows)->contains(fn (array $row): bool => $row['status'] === SupplierFuelInvoiceRebillingCoverageLine::STATUS_NEGATIVE_MARGIN);
            $coverageStatus = $hasMissing
                ? SupplierFuelInvoiceRebillingCoverage::STATUS_INCOMPLETE
                : ($hasNegative ? SupplierFuelInvoiceRebillingCoverage::STATUS_NEGATIVE_MARGIN : SupplierFuelInvoiceRebillingCoverage::STATUS_COMPLETE);

            $coverage = SupplierFuelInvoiceRebillingCoverage::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'billing_document_id' => $document->getKey(), 'idempotency_key' => (string) $data['idempotency_key'],
                'command_fingerprint' => $fingerprint, 'comparison_basis' => $basis, 'currency' => $document->currency,
                'purchase_amount_minor' => $purchaseTotal, 'rebilled_amount_minor' => $rebilledTotal,
                'unrebilled_amount_minor' => $unrebilledTotal, 'margin_minor' => $marginTotal,
                'status' => $coverageStatus,
                'source_snapshot' => ['invoice_public_id' => $invoicePublicId, 'active_allocation_count' => count($rows),
                    'bank_matching_performed' => false, 'payment_marked' => false, 'settlement_mutated' => false],
                'revision' => 1, 'evaluated_by_user_id' => $actor->getKey(), 'evaluated_at' => now(),
            ]);

            foreach ($rows as $index => $row) {
                $application = $row['application'];
                $statementLine = $row['statementLine'];
                $statement = $row['statement'];
                SupplierFuelInvoiceRebillingCoverageLine::query()->create([
                    'public_id' => (string) Str::uuid(), 'coverage_id' => $coverage->getKey(),
                    'supplier_fuel_invoice_transaction_allocation_id' => $row['allocation']->getKey(),
                    'fuel_transaction_id' => $row['transaction']->getKey(),
                    'fuel_transaction_settlement_application_id' => $application?->getKey(),
                    'financial_calculation_id' => $application?->financial_calculation_id,
                    'financial_settlement_statement_id' => $statement?->getKey(),
                    'output_billing_document_id' => $statement?->billing_document_id,
                    'recipient_type' => $application?->settlement_target,
                    'recipient_organization_id' => $application?->target_organization_id,
                    'recipient_driver_id' => $application?->target_driver_id,
                    'comparison_basis' => $basis, 'purchase_amount_minor' => $row['purchaseMinor'],
                    'rebilled_amount_minor' => $row['rebilledMinor'], 'unrebilled_amount_minor' => $row['unrebilledMinor'],
                    'margin_minor' => $row['marginMinor'], 'currency' => $document->currency, 'status' => $row['status'],
                    'source_snapshot' => ['allocation_public_id' => $row['allocation']->public_id,
                        'fuel_transaction_public_id' => $row['transaction']->public_id,
                        'settlement_application_public_id' => $application?->public_id,
                        'statement_line_public_id' => $statementLine?->public_id,
                        'statement_public_id' => $statement?->public_id,
                        'output_kind' => $statement?->output_kind],
                    'position' => $index + 1, 'created_at' => now(),
                ]);
            }

            SupplierFuelInvoiceRebillingCoverageEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'coverage_id' => $coverage->getKey(), 'revision' => 1,
                'event_type' => 'evaluated', 'payload' => ['status' => $coverageStatus,
                    'purchase_amount_minor' => $purchaseTotal, 'rebilled_amount_minor' => $rebilledTotal,
                    'unrebilled_amount_minor' => $unrebilledTotal, 'margin_minor' => $marginTotal,
                    'positive_margin_allowed' => true, 'financial_side_effects_performed' => false],
                'actor_user_id' => $actor->getKey(), 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($coverage->refresh()), 'replayed' => false];
        });
    }

    private function statementMatchesApplication(
        FinancialSettlementStatement $statement,
        FuelTransactionSettlementApplication $application,
    ): bool {
        if ($application->settlement_target === 'driver') {
            return $statement->recipient_type === FinancialSettlementStatement::RECIPIENT_DRIVER
                && (int) $statement->recipient_driver_id === (int) $application->target_driver_id
                && in_array($statement->output_kind, [
                    FinancialSettlementStatement::OUTPUT_DRIVER_DEDUCTION,
                    FinancialSettlementStatement::OUTPUT_DRIVER_PAYOUT,
                ], true);
        }

        return $application->settlement_target === 'carrier'
            && $statement->recipient_type === FinancialSettlementStatement::RECIPIENT_ORGANIZATION
            && (int) $statement->recipient_organization_id === (int) $application->target_organization_id
            && in_array($statement->output_kind, [
                FinancialSettlementStatement::OUTPUT_CARRIER_RECEIVABLE,
                FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            ], true);
    }

    private function proportion(int $amountMinor, int $partMinor, int $wholeMinor): int
    {
        return intdiv(($amountMinor * $partMinor) + intdiv($wholeMinor, 2), $wholeMinor);
    }

    private function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad($fraction, 3, '0');
        $minor = ((int) $whole * 100) + (int) substr($fraction, 0, 2);

        return $minor + ((int) $fraction[2] >= 5 ? 1 : 0);
    }

    private function present(SupplierFuelInvoiceRebillingCoverage $coverage): array
    {
        $coverage->loadMissing(['lines', 'events']);

        return [
            'public_id' => $coverage->public_id, 'comparison_basis' => $coverage->comparison_basis,
            'currency' => $coverage->currency, 'purchase_amount_minor' => (int) $coverage->purchase_amount_minor,
            'rebilled_amount_minor' => (int) $coverage->rebilled_amount_minor,
            'unrebilled_amount_minor' => (int) $coverage->unrebilled_amount_minor,
            'margin_minor' => (int) $coverage->margin_minor, 'status' => $coverage->status,
            'revision' => (int) $coverage->revision, 'lines' => $coverage->lines->toArray(),
            'events' => $coverage->events->toArray(), 'positive_margin_allowed' => true,
            'bank_matching_performed' => false, 'payment_marked' => false, 'settlement_mutated' => false,
        ];
    }
}
