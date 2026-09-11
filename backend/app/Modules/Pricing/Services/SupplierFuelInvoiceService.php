<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\BillingDocumentLine;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;
use App\Modules\Pricing\Models\SupplierFuelInvoiceRebillingCoverage;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierFuelInvoiceService
{
    public function store(array $data, int $organizationId, ?User $actor): array
    {
        if (! $actor instanceof User || ! $actor->can('compensation.manage')) {
            abort(403);
        }

        $normalized = $this->normalized($data);
        $fingerprint = hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($normalized, $fingerprint, $organizationId, $actor): array {
            $existing = BillingDocumentCommercialIdentity::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $normalized['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existing instanceof BillingDocumentCommercialIdentity) {
                if ($existing->command_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key is already used for another command.']]);
                }

                return ['data' => $this->present($existing), 'replayed' => true];
            }

            $duplicateNumber = BillingDocumentCommercialIdentity::query()
                ->where('owner_organization_id', $organizationId)
                ->where('document_number', $normalized['document_number'])
                ->lockForUpdate()
                ->exists();

            if ($duplicateNumber) {
                throw ValidationException::withMessages(['document_number' => ['The supplier document number already exists.']]);
            }

            $net = $this->minor($normalized['net_amount']);
            $vat = $this->minor($normalized['vat_amount']);
            $gross = $this->minor($normalized['gross_amount']);
            if ($net + $vat !== $gross) {
                throw ValidationException::withMessages(['gross_amount' => ['Gross amount must equal net amount plus VAT.']]);
            }
            if ((int) round($net * $normalized['vat_rate_basis_points'] / 10000) !== $vat) {
                throw ValidationException::withMessages(['vat_amount' => ['VAT amount does not match net amount and VAT rate.']]);
            }

            $taxDate = $normalized['taxable_supply_on'] ?? $normalized['issued_on'];
            $document = BillingDocument::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'counterparty_organization_id' => null, 'driver_id' => null,
                'document_type' => BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE,
                'period_from' => $taxDate, 'period_until' => $taxDate,
                'currency' => $normalized['currency'], 'vat_treatment' => BillingDocument::VAT_STANDARD,
                'vat_status_snapshot' => 'payer', 'net_amount' => $normalized['net_amount'],
                'vat_rate' => number_format($normalized['vat_rate_basis_points'] / 100, 2, '.', ''),
                'vat_amount' => $normalized['vat_amount'], 'gross_amount' => $normalized['gross_amount'],
                'status' => 'draft', 'source_snapshot' => ['source' => 'supplier_fuel_invoice_manual',
                    'bank_matching_performed' => false, 'payment_marked' => false,
                    'fuel_transaction_allocation_performed' => false], 'created_by_user_id' => $actor->id,
            ]);

            BillingDocumentLine::query()->create([
                'billing_document_id' => $document->id, 'financial_calculation_id' => null,
                'description' => $normalized['description'], 'quantity' => '1.000',
                'unit_rate' => $normalized['net_amount'], 'net_amount' => $normalized['net_amount'],
                'vat_amount' => $normalized['vat_amount'], 'gross_amount' => $normalized['gross_amount'],
                'position' => 1, 'created_at' => now(),
            ]);

            $snapshot = [
                'name' => $normalized['counterparty_name'],
                'registration_number' => $normalized['counterparty_registration_number'],
                'vat_number' => $normalized['counterparty_vat_number'],
                'account_identifier' => $normalized['counterparty_account_identifier'],
            ];
            $identity = BillingDocumentCommercialIdentity::query()->create([
                'public_id' => (string) Str::uuid(), 'billing_document_id' => $document->id,
                'owner_organization_id' => $organizationId, 'direction' => BillingDocumentCommercialIdentity::DIRECTION_PAYABLE,
                'document_number' => $normalized['document_number'], 'variable_symbol' => $normalized['variable_symbol'],
                'issued_on' => $normalized['issued_on'], 'taxable_supply_on' => $normalized['taxable_supply_on'],
                'due_on' => $normalized['due_on'], 'counterparty_name' => $normalized['counterparty_name'],
                'counterparty_registration_number' => $normalized['counterparty_registration_number'],
                'counterparty_vat_number' => $normalized['counterparty_vat_number'],
                'counterparty_account_identifier' => $normalized['counterparty_account_identifier'],
                'counterparty_snapshot' => $snapshot, 'revision' => 1,
                'idempotency_key' => $normalized['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'created_by_user_id' => $actor->id,
            ]);
            BillingDocumentCommercialIdentityEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'billing_document_commercial_identity_id' => $identity->id,
                'revision' => 1, 'event_type' => 'created', 'reason' => 'Supplier fuel invoice draft created.',
                'evidence' => ['billing_document_public_id' => $document->public_id,
                    'bank_matching_performed' => false, 'payment_marked' => false,
                    'fuel_transaction_allocation_performed' => false], 'occurred_at' => now(), 'actor_user_id' => $actor->id,
            ]);

            return ['data' => $this->present($identity), 'replayed' => false];
        });
    }

    public function index(array $filters, int $organizationId, ?User $actor): array
    {
        $this->ensureCanManage($actor);
        $query = BillingDocumentCommercialIdentity::query()
            ->where('owner_organization_id', $organizationId)
            ->where('direction', BillingDocumentCommercialIdentity::DIRECTION_PAYABLE)
            ->whereHas('billingDocument', function ($query): void {
                $query->where('document_type', BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE);
            });

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhere('variable_symbol', 'like', '%'.$search.'%')
                    ->orWhere('counterparty_name', 'like', '%'.$search.'%');
            });
        }
        if (isset($filters['status'])) {
            $status = (string) $filters['status'];
            $query->whereHas('billingDocument', function ($query) use ($status): void {
                $query->where('document_type', BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE)
                    ->where('status', $status);
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $paginator = $query->orderByDesc('issued_on')->orderByDesc('id')->paginate($perPage);
        $items = [];
        foreach ($paginator->items() as $identity) {
            if ($identity instanceof BillingDocumentCommercialIdentity) {
                $items[] = $this->present($identity);
            }
        }

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'bank_matching_performed' => false,
            'payment_marked' => false,
        ];
    }

    public function show(string $publicId, int $organizationId, ?User $actor): array
    {
        $this->ensureCanManage($actor);
        $identity = BillingDocumentCommercialIdentity::query()
            ->where('public_id', $publicId)
            ->where('owner_organization_id', $organizationId)
            ->where('direction', BillingDocumentCommercialIdentity::DIRECTION_PAYABLE)
            ->whereHas('billingDocument', function ($query): void {
                $query->where('document_type', BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE);
            })
            ->firstOrFail();

        return $this->present($identity);
    }

    private function ensureCanManage(?User $actor): void
    {
        if (! $actor instanceof User || ! $actor->can('compensation.manage')) {
            abort(403);
        }
    }

    private function normalized(array $data): array
    {
        $nullable = static fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null;

        return [
            'idempotency_key' => (string) $data['idempotency_key'], 'document_number' => trim((string) $data['document_number']),
            'variable_symbol' => $nullable($data['variable_symbol'] ?? null), 'issued_on' => (string) $data['issued_on'],
            'taxable_supply_on' => $nullable($data['taxable_supply_on'] ?? null), 'due_on' => (string) $data['due_on'],
            'counterparty_name' => trim((string) $data['counterparty_name']),
            'counterparty_registration_number' => $nullable($data['counterparty_registration_number'] ?? null),
            'counterparty_vat_number' => $nullable($data['counterparty_vat_number'] ?? null),
            'counterparty_account_identifier' => $nullable($data['counterparty_account_identifier'] ?? null),
            'currency' => strtoupper((string) $data['currency']), 'description' => trim((string) $data['description']),
            'net_amount' => $this->money((string) $data['net_amount']),
            'vat_rate_basis_points' => (int) $data['vat_rate_basis_points'],
            'vat_amount' => $this->money((string) $data['vat_amount']),
            'gross_amount' => $this->money((string) $data['gross_amount']),
        ];
    }

    private function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function money(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return $whole.'.'.str_pad($fraction, 2, '0');
    }

    private function dateTimeValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && $value !== '') {
            return (new \DateTimeImmutable($value))->format(DATE_ATOM);
        }

        return null;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        return null;
    }

    private function present(BillingDocumentCommercialIdentity $identity): array
    {
        $identity->loadMissing(['billingDocument.lines', 'events']);
        $document = $identity->billingDocument;
        if (! $document instanceof BillingDocument) {
            throw new \LogicException('Supplier fuel invoice has no billing document.');
        }

        $allocations = [];
        $activeAllocatedMinor = 0;
        $allocationModels = SupplierFuelInvoiceTransactionAllocation::query()
            ->where('billing_document_id', $document->getKey())
            ->with(['fuelTransaction', 'events'])
            ->orderBy('id')
            ->get();
        foreach ($allocationModels as $allocation) {
            if (! $allocation instanceof SupplierFuelInvoiceTransactionAllocation) {
                continue;
            }
            if ($allocation->status === SupplierFuelInvoiceTransactionAllocation::STATUS_ACTIVE) {
                $activeAllocatedMinor += (int) $allocation->allocated_amount_minor;
            }
            $allocations[] = $this->presentAllocation($allocation);
        }

        $invoiceMinor = $this->minor((string) $document->gross_amount);
        $unallocatedMinor = $invoiceMinor - $activeAllocatedMinor;
        $allocationState = $activeAllocatedMinor === 0
            ? 'unallocated'
            : ($unallocatedMinor === 0 ? 'fully_allocated' : 'partially_allocated');

        $paidMinor = (int) SupplierFuelInvoiceBankPayment::query()
            ->where('billing_document_id', $document->getKey())
            ->where('status', SupplierFuelInvoiceBankPayment::STATUS_ACTIVE)
            ->sum('allocated_amount_minor');
        $unpaidMinor = max($invoiceMinor - $paidMinor, 0);
        $paymentState = $paidMinor === 0 ? 'unpaid' : ($unpaidMinor === 0 ? 'paid' : 'partially_paid');

        $coverage = SupplierFuelInvoiceRebillingCoverage::query()
            ->where('owner_organization_id', (int) $document->owner_organization_id)
            ->where('billing_document_id', $document->getKey())
            ->with('lines')
            ->orderByDesc('id')
            ->first();
        $coverageSummary = $coverage instanceof SupplierFuelInvoiceRebillingCoverage
            ? [
                'public_id' => $coverage->public_id,
                'comparison_basis' => $coverage->comparison_basis,
                'state' => $coverage->status,
                'purchase_amount_minor' => (int) $coverage->purchase_amount_minor,
                'rebilled_amount_minor' => (int) $coverage->rebilled_amount_minor,
                'unrebilled_amount_minor' => (int) $coverage->unrebilled_amount_minor,
                'margin_minor' => (int) $coverage->margin_minor,
                'currency' => $coverage->currency,
                'evaluated_at' => $this->dateTimeValue($coverage->getAttribute('evaluated_at')),
                'lines' => $coverage->lines->toArray(),
            ]
            : [
                'public_id' => null,
                'comparison_basis' => null,
                'state' => 'not_evaluated',
                'purchase_amount_minor' => $invoiceMinor,
                'rebilled_amount_minor' => 0,
                'unrebilled_amount_minor' => $invoiceMinor,
                'margin_minor' => -$invoiceMinor,
                'currency' => $document->currency,
                'evaluated_at' => null,
                'lines' => [],
            ];

        return [
            'public_id' => $identity->public_id,
            'direction' => $identity->direction,
            'document_number' => $identity->document_number,
            'variable_symbol' => $identity->variable_symbol,
            'issued_on' => $this->dateValue($identity->getAttribute('issued_on')),
            'taxable_supply_on' => $this->dateValue($identity->getAttribute('taxable_supply_on')),
            'due_on' => $this->dateValue($identity->getAttribute('due_on')),
            'counterparty_snapshot' => $identity->counterparty_snapshot,
            'revision' => (int) $identity->revision,
            'billing_document' => $document->toArray(),
            'allocation_summary' => [
                'state' => $allocationState,
                'invoice_amount_minor' => $invoiceMinor,
                'active_allocated_amount_minor' => $activeAllocatedMinor,
                'unallocated_amount_minor' => $unallocatedMinor,
                'currency' => $document->currency,
            ],
            'allocations' => $allocations,
            'events' => $identity->events->toArray(),
            'payment_summary' => [
                'state' => $paymentState,
                'invoice_amount_minor' => $invoiceMinor,
                'paid_amount_minor' => $paidMinor,
                'unpaid_amount_minor' => $unpaidMinor,
                'currency' => $document->currency,
            ],
            'rebilling_coverage_summary' => $coverageSummary,
            'automatic_bank_matching_performed' => false,
            'bank_matching_performed' => $paidMinor > 0,
            'payment_marked' => $paymentState === 'paid',
            'fuel_transaction_allocation_performed' => $activeAllocatedMinor > 0,
        ];
    }

    private function presentAllocation(SupplierFuelInvoiceTransactionAllocation $allocation): array
    {
        $transaction = $allocation->fuelTransaction;
        if (! $transaction instanceof FuelTransaction) {
            throw new \LogicException('Supplier fuel invoice allocation has no fuel transaction.');
        }

        return [
            'public_id' => $allocation->public_id,
            'allocated_amount_minor' => (int) $allocation->allocated_amount_minor,
            'currency' => $allocation->currency,
            'status' => $allocation->status,
            'revision' => (int) $allocation->revision,
            'reason' => $allocation->reason,
            'reversed_at' => $allocation->getAttribute('reversed_at'),
            'fuel_transaction' => [
                'public_id' => $transaction->public_id,
                'provider' => $transaction->provider,
                'provider_transaction_identifier' => $transaction->provider_transaction_identifier,
                'occurred_at' => $transaction->getAttribute('occurred_at'),
                'station_name' => $transaction->station_name,
                'product_name' => $transaction->product_name,
                'quantity' => $transaction->quantity,
                'unit_of_measure' => $transaction->unit_of_measure,
                'gross_amount' => $transaction->gross_amount,
                'currency' => $transaction->currency,
                'invoice_reference' => $transaction->invoice_reference,
            ],
            'events' => $allocation->events->toArray(),
        ];
    }
}
