<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\BillingDocumentLine;
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

        return ['public_id' => $identity->public_id, 'direction' => $identity->direction,
            'document_number' => $identity->document_number, 'variable_symbol' => $identity->variable_symbol,
            'issued_on' => $this->dateValue($identity->getAttribute('issued_on')),
            'taxable_supply_on' => $this->dateValue($identity->getAttribute('taxable_supply_on')),
            'due_on' => $this->dateValue($identity->getAttribute('due_on')),
            'counterparty_snapshot' => $identity->counterparty_snapshot,
            'revision' => $identity->revision, 'billing_document' => $identity->billingDocument?->toArray(),
            'events' => $identity->events->toArray(), 'bank_matching_performed' => false,
            'payment_marked' => false, 'fuel_transaction_allocation_performed' => false];
    }
}
