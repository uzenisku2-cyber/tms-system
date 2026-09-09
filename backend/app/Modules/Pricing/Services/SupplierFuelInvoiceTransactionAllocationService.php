<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocation;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierFuelInvoiceTransactionAllocationService
{
    public function store(string $invoicePublicId, array $data, int $organizationId, ?User $actor): array
    {
        if (! $actor instanceof User || ! $actor->can('compensation.manage')) {
            abort(403);
        }

        $amountMinor = $this->minor((string) $data['allocated_amount']);
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages(['allocated_amount' => ['Allocated amount must be greater than zero.']]);
        }

        $command = [
            'invoice_public_id' => $invoicePublicId,
            'fuel_transaction_public_id' => (string) $data['fuel_transaction_public_id'],
            'allocated_amount_minor' => $amountMinor,
            'reason' => trim((string) $data['reason']),
        ];
        $fingerprint = hash('sha256', json_encode($command, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($invoicePublicId, $data, $organizationId, $actor, $amountMinor, $fingerprint): array {
            $existing = SupplierFuelInvoiceTransactionAllocation::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', (string) $data['idempotency_key'])
                ->lockForUpdate()->first();
            if ($existing instanceof SupplierFuelInvoiceTransactionAllocation) {
                if ($existing->command_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key is already used for another command.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            $identity = BillingDocumentCommercialIdentity::query()
                ->where('public_id', $invoicePublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()->firstOrFail();
            $document = BillingDocument::query()->whereKey($identity->billing_document_id)
                ->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            if ($document->document_type !== BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE || $identity->direction !== BillingDocumentCommercialIdentity::DIRECTION_PAYABLE) {
                throw ValidationException::withMessages(['supplier_fuel_invoice' => ['The document is not a payable supplier fuel invoice.']]);
            }
            if ($document->status !== 'draft') {
                throw ValidationException::withMessages(['supplier_fuel_invoice' => ['Only a draft supplier fuel invoice may be allocated.']]);
            }

            $transaction = FuelTransaction::query()->where('public_id', (string) $data['fuel_transaction_public_id'])
                ->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            if ($transaction->currency !== $document->currency) {
                throw ValidationException::withMessages(['fuel_transaction_public_id' => ['Fuel transaction currency does not match the invoice.']]);
            }

            $invoiceTotalMinor = $this->minor((string) $document->gross_amount);
            $transactionTotalMinor = $this->minor((string) $transaction->gross_amount);
            $invoiceAllocatedMinor = (int) SupplierFuelInvoiceTransactionAllocation::query()
                ->where('billing_document_id', $document->getKey())->where('status', SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            $transactionAllocatedMinor = (int) SupplierFuelInvoiceTransactionAllocation::query()
                ->where('fuel_transaction_id', $transaction->getKey())->where('status', SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE)
                ->sum('allocated_amount_minor');
            if ($invoiceAllocatedMinor + $amountMinor > $invoiceTotalMinor) {
                throw ValidationException::withMessages(['allocated_amount' => ['Allocation exceeds the remaining supplier invoice amount.']]);
            }
            if ($transactionAllocatedMinor + $amountMinor > $transactionTotalMinor) {
                throw ValidationException::withMessages(['allocated_amount' => ['Allocation exceeds the remaining fuel transaction amount.']]);
            }

            $allocation = SupplierFuelInvoiceTransactionAllocation::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'billing_document_id' => $document->getKey(), 'fuel_transaction_id' => $transaction->getKey(),
                'idempotency_key' => (string) $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'allocated_amount_minor' => $amountMinor, 'currency' => $document->currency,
                'status' => SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE, 'revision' => 1,
                'reason' => trim((string) $data['reason']), 'created_by_user_id' => $actor->getKey(),
            ]);
            SupplierFuelInvoiceTransactionAllocationEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'allocation_id' => $allocation->getKey(), 'revision' => 1,
                'event_type' => SupplierFuelInvoiceTransactionAllocationEvent::TYPE_ALLOCATED,
                'reason' => $allocation->reason,
                'evidence' => ['invoice_public_id' => $identity->public_id, 'fuel_transaction_public_id' => $transaction->public_id,
                    'allocated_amount_minor' => $amountMinor, 'currency' => $document->currency,
                    'bank_matching_performed' => false, 'payment_marked' => false, 'fuel_settlement_mutated' => false],
                'actor_user_id' => $actor->getKey(), 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($allocation->refresh()), 'replayed' => false];
        });
    }

    public function reverse(
        string $invoicePublicId,
        string $allocationPublicId,
        array $data,
        int $organizationId,
        ?User $actor,
    ): array {
        if (! $actor instanceof User || ! $actor->can('compensation.manage')) {
            abort(403);
        }

        return DB::transaction(function () use ($invoicePublicId, $allocationPublicId, $data, $organizationId, $actor): array {
            $identity = BillingDocumentCommercialIdentity::query()
                ->where('public_id', $invoicePublicId)
                ->where('owner_organization_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();
            $allocation = SupplierFuelInvoiceTransactionAllocation::query()
                ->where('public_id', $allocationPublicId)
                ->where('owner_organization_id', $organizationId)
                ->where('billing_document_id', $identity->billing_document_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $allocation->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The allocation revision is stale.']]);
            }
            if ($allocation->status !== SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['allocation' => ['Only an active allocation may be reversed.']]);
            }

            $nextRevision = (int) $allocation->revision + 1;
            $reason = trim((string) $data['reason']);
            $allocation->forceFill([
                'status' => SupplierFuelInvoiceTransactionAllocation::STATUS_REVERSED,
                'revision' => $nextRevision,
                'reversed_by_user_id' => $actor->getKey(),
                'reversed_at' => now(),
            ])->save();
            SupplierFuelInvoiceTransactionAllocationEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'allocation_id' => $allocation->getKey(),
                'revision' => $nextRevision,
                'event_type' => SupplierFuelInvoiceTransactionAllocationEvent::TYPE_REVERSED,
                'reason' => $reason,
                'evidence' => [
                    'invoice_public_id' => $identity->public_id,
                    'allocated_amount_minor' => (int) $allocation->allocated_amount_minor,
                    'currency' => $allocation->currency,
                    'bank_matching_performed' => false,
                    'payment_marked' => false,
                    'fuel_settlement_mutated' => false,
                ],
                'actor_user_id' => $actor->getKey(),
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($allocation->refresh())];
        });
    }

    private function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');
        if (strlen($fraction) > 2 && trim(substr($fraction, 2), '0') !== '') {
            throw ValidationException::withMessages(['allocated_amount' => ['Amount must be exact to whole cents.']]);
        }

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }

    private function present(SupplierFuelInvoiceTransactionAllocation $allocation): array
    {
        $allocation->loadMissing(['billingDocument.commercialIdentity', 'fuelTransaction', 'events']);
        $document = $allocation->billingDocument;
        if (! $document instanceof BillingDocument) {
            throw new \LogicException('Supplier fuel invoice allocation has no billing document.');
        }

        $transaction = $allocation->fuelTransaction;
        if (! $transaction instanceof FuelTransaction) {
            throw new \LogicException('Supplier fuel invoice allocation has no fuel transaction.');
        }
        $allocatedMinor = (int) SupplierFuelInvoiceTransactionAllocation::query()
            ->where('billing_document_id', $allocation->billing_document_id)
            ->where('status', SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE)->sum('allocated_amount_minor');
        $invoiceMinor = $this->minor((string) $document->gross_amount);
        $state = $allocatedMinor === 0 ? 'unallocated' : ($allocatedMinor === $invoiceMinor ? 'fully_allocated' : 'partially_allocated');

        return [
            'public_id' => $allocation->public_id,
            'supplier_fuel_invoice_public_id' => $document->commercialIdentity?->public_id,
            'fuel_transaction_public_id' => $transaction->public_id,
            'allocated_amount_minor' => (int) $allocation->allocated_amount_minor,
            'currency' => $allocation->currency, 'status' => $allocation->status, 'revision' => (int) $allocation->revision,
            'invoice_allocation_state' => $state, 'invoice_allocated_amount_minor' => $allocatedMinor,
            'invoice_unallocated_amount_minor' => $invoiceMinor - $allocatedMinor,
            'events' => $allocation->events->toArray(), 'bank_matching_performed' => false,
            'payment_marked' => false, 'fuel_settlement_mutated' => false,
        ];
    }
}
