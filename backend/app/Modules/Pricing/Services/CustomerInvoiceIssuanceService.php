<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\OrganizationInvoicePaymentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CustomerInvoiceIssuanceService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly DepotApprovedCalculationGuard $depotApprovals,
    ) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function issue(User $actor, string $publicId, array $data): array
    {
        $ownerId = $this->organizationContext->requireId();
        $fingerprint = hash('sha256', json_encode([$publicId, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $publicId, $data, $ownerId, $fingerprint): array {
            $owner = Organization::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();
            if ($owner->type !== Organization::TYPE_MASTER) {
                abort(403);
            }
            $identityByKey = BillingDocumentCommercialIdentity::query()
                ->where('owner_organization_id', $ownerId)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($identityByKey instanceof BillingDocumentCommercialIdentity) {
                $documentByKey = BillingDocument::query()->findOrFail($identityByKey->billing_document_id);
                if ($documentByKey->public_id !== $publicId || $identityByKey->command_fingerprint !== $fingerprint) {
                    $this->invalid('idempotency_key', 'The idempotency key was used for another command.');
                }

                return $this->present($documentByKey, $identityByKey);
            }
            $document = BillingDocument::query()->where('public_id', $publicId)
                ->where('owner_organization_id', $ownerId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->lockForUpdate()->firstOrFail();
            if ($document->status !== 'draft' || $document->commercialIdentity()->exists()) {
                $this->invalid('customer_invoice', 'Only a draft without commercial identity can be issued.');
            }
            if (BillingDocumentCommercialIdentity::query()->where('owner_organization_id', $ownerId)
                ->where('document_number', $data['document_number'])->exists()) {
                $this->invalid('document_number', 'The document number is already used.');
            }
            $snapshot = $document->source_snapshot;
            if (! is_array($snapshot) || ($snapshot['source'] ?? null) !== 'final_financial_calculations') {
                $this->invalid('customer_invoice', 'Only a verified calculation draft can be issued.');
            }
            $issuer = $this->party(Organization::query()->findOrFail($ownerId), true);
            $customer = $this->party(Organization::query()->findOrFail($document->counterparty_organization_id), false);
            if ($document->vat_treatment === BillingDocument::VAT_STANDARD && $issuer['vat_number'] === null) {
                $this->invalid('customer_invoice', 'A payer issuer must have a VAT number.');
            }
            if ($document->vat_treatment === BillingDocument::VAT_STANDARD && empty($data['taxable_supply_on'])) {
                $this->invalid('taxable_supply_on', 'Taxable supply date is required for a payer invoice.');
            }
            if (! empty($data['taxable_supply_on'])
                && ($data['taxable_supply_on'] < (string) $document->getRawOriginal('period_from')
                    || $data['taxable_supply_on'] > (string) $document->getRawOriginal('period_until'))) {
                $this->invalid('taxable_supply_on', 'Taxable supply date must fall within the billing period.');
            }
            $lines = $document->lines()->with('financialCalculation')->get();
            if ($lines->isEmpty()) {
                $this->invalid('customer_invoice', 'An invoice must have at least one line.');
            }
            $netCents = 0;
            $vatCents = 0;
            $grossCents = 0;
            $lineSnapshot = [];
            foreach ($lines as $line) {
                if ($line->financialCalculation === null) {
                    $this->invalid('customer_invoice', 'Every invoice line must reference a current calculation.');
                }
                $this->depotApprovals->assertApproved($line->financialCalculation);
                $netCents += $this->cents((string) $line->net_amount);
                $vatCents += $this->cents((string) $line->vat_amount);
                $grossCents += $this->cents((string) $line->gross_amount);
                $lineSnapshot[] = [
                    'position' => (int) $line->position, 'description' => (string) $line->description,
                    'quantity' => (string) $line->quantity, 'unit_rate' => (string) $line->unit_rate,
                    'net_amount' => (string) $line->net_amount, 'vat_amount' => (string) $line->vat_amount,
                    'gross_amount' => (string) $line->gross_amount,
                    'calculation_public_id' => $line->financialCalculation->public_id,
                ];
            }
            if ($netCents !== $this->cents((string) $document->net_amount)
                || $vatCents !== $this->cents((string) $document->vat_amount)
                || $grossCents !== $this->cents((string) $document->gross_amount)) {
                $this->invalid('customer_invoice', 'Invoice lines do not match document totals.');
            }
            $paymentAccount = OrganizationInvoicePaymentAccount::query()
                ->where('organization_id', $ownerId)->orderByDesc('revision')->first();
            $issuedSnapshot = [
                'payment_iban' => $paymentAccount?->iban,
                'payment_account_holder' => $paymentAccount?->account_holder,
                'payment_account_revision' => $paymentAccount?->revision,
                'issuer' => $issuer, 'customer' => $customer,
                'document_number' => $data['document_number'],
                'issued_on' => $data['issued_on'], 'taxable_supply_on' => $data['taxable_supply_on'] ?? null,
                'due_on' => $data['due_on'], 'currency' => $document->currency,
                'vat_treatment' => $document->vat_treatment, 'vat_rate' => $document->vat_rate,
                'net_amount' => $document->net_amount, 'vat_amount' => $document->vat_amount,
                'gross_amount' => $document->gross_amount,
                'period_from' => (string) $document->getRawOriginal('period_from'),
                'period_until' => (string) $document->getRawOriginal('period_until'),
                'lines' => $lineSnapshot, 'source_snapshot' => $snapshot,
            ];
            $contentHash = hash('sha256', json_encode($issuedSnapshot, JSON_THROW_ON_ERROR));
            $identity = BillingDocumentCommercialIdentity::query()->create([
                'billing_document_id' => $document->id, 'owner_organization_id' => $ownerId,
                'idempotency_key' => $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'direction' => BillingDocumentCommercialIdentity::DIRECTION_RECEIVABLE,
                'document_number' => $data['document_number'],
                'variable_symbol' => $data['variable_symbol'] ?? null,
                'issued_on' => $data['issued_on'], 'taxable_supply_on' => $data['taxable_supply_on'] ?? null,
                'due_on' => $data['due_on'], 'counterparty_name' => $customer['name'],
                'counterparty_registration_number' => $customer['registration_number'],
                'counterparty_vat_number' => $customer['vat_number'],
                'counterparty_account_identifier' => null,
                'counterparty_snapshot' => $issuedSnapshot, 'revision' => 1,
                'created_by_user_id' => $actor->id,
            ]);
            BillingDocumentCommercialIdentityEvent::query()->create([
                'billing_document_commercial_identity_id' => $identity->id,
                'revision' => 1, 'event_type' => 'approved',
                'reason' => $data['reason'],
                'evidence' => ['content_sha256' => $contentHash, 'billing_document_public_id' => $publicId],
                'occurred_at' => now(), 'actor_user_id' => $actor->id,
            ]);
            $document->forceFill([
                'status' => 'approved', 'approved_by_user_id' => $actor->id, 'approved_at' => now(),
            ])->save();

            return $this->present($document, $identity);
        });
    }

    /** @return array<string, mixed> */
    private function present(BillingDocument $document, BillingDocumentCommercialIdentity $identity): array
    {
        return [
            'public_id' => $document->public_id,
            'status' => $document->status,
            'document_number' => $identity->document_number,
            'issued_on' => (string) $identity->getRawOriginal('issued_on'),
            'due_on' => (string) $identity->getRawOriginal('due_on'),
            'content' => $identity->counterparty_snapshot,
        ];
    }

    /** @return array<string, mixed> */
    private function party(Organization $organization, bool $issuer): array
    {
        $fields = ['name', 'registration_number', 'vat_number', 'street', 'city', 'postal_code', 'country_code'];
        $result = [];
        foreach ($fields as $field) {
            $value = $organization->getAttribute($field);
            $result[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }
        foreach (['name', 'street', 'city', 'postal_code', 'country_code'] as $field) {
            if ($result[$field] === null) {
                $this->invalid('customer_invoice', ($issuer ? 'Issuer' : 'Customer').' has incomplete identity or address.');
            }
        }

        return $result;
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
