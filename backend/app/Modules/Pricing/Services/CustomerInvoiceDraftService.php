<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentLine;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\OrganizationTaxProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CustomerInvoiceDraftService
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(User $actor, array $data): array
    {
        $ownerId = $this->organizationContext->requireId();
        $ids = $data['calculation_public_ids'];
        sort($ids, SORT_STRING);
        $fingerprint = hash('sha256', json_encode([
            (int) $data['customer_organization_id'], $data['period_from'], $data['period_until'], $ids,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $data, $ownerId, $ids, $fingerprint): array {
            $owner = Organization::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();
            if ($owner->type !== Organization::TYPE_MASTER) {
                abort(403);
            }
            $existing = BillingDocument::query()->where('owner_organization_id', $ownerId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->where('source_snapshot->draft_idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing instanceof BillingDocument) {
                $snapshot = $existing->source_snapshot;
                if (($snapshot['command_fingerprint'] ?? null) !== $fingerprint) {
                    $this->invalid('idempotency_key', 'This key was used for different invoice content.');
                }

                return $this->present($existing);
            }

            $customer = Organization::query()->findOrFail((int) $data['customer_organization_id']);
            if ($customer->id === $ownerId) {
                $this->invalid('customer_organization_id', 'The customer must differ from the issuer.');
            }
            $profile = OrganizationTaxProfile::query()->where('organization_id', $ownerId)
                ->effectiveOn($data['period_from'])
                ->where(static fn ($query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $data['period_until']))
                ->orderByDesc('valid_from')->first();
            if (! $profile instanceof OrganizationTaxProfile || $profile->verified_at === null) {
                $this->invalid('period_from', 'A verified tax profile must cover the full period.');
            }
            $payer = $profile->vat_status === OrganizationTaxProfile::VAT_STATUS_PAYER;
            if (! $payer && $profile->vat_status !== OrganizationTaxProfile::VAT_STATUS_NON_PAYER) {
                $this->invalid('period_from', 'The tax profile has an unsupported status.');
            }
            $rateBasisPoints = $payer ? (int) round((float) $profile->vat_rate * 100) : 0;
            if ($payer && ($profile->vat_rate === null || $rateBasisPoints < 0 || $rateBasisPoints > 10000)) {
                $this->invalid('period_from', 'The tax profile has an invalid rate.');
            }

            $calculations = FinancialCalculation::query()->whereIn('public_id', $ids)
                ->where('organization_id', $ownerId)
                ->orderBy('public_id')->lockForUpdate()->get();
            if ($calculations->count() !== count($ids)) {
                $this->invalid('calculation_public_ids', 'One or more calculations are unavailable.');
            }
            /** @var array<int, string> $serviceDates */
            $serviceDates = [];
            $net = 0;
            $currency = null;
            foreach ($calculations as $calculation) {
                if (! in_array($calculation->status, [FinancialCalculation::STATUS_APPROVED, FinancialCalculation::STATUS_CLOSED], true)
                    || FinancialCalculation::query()->where('supersedes_calculation_id', $calculation->id)->exists()) {
                    $this->invalid('calculation_public_ids', 'Only current approved or closed calculations are eligible.');
                }
                $priceList = $calculation->priceList;
                if ($priceList === null || (int) $priceList->provider_organization_id !== $ownerId
                    || (int) $priceList->customer_organization_id !== (int) $customer->id) {
                    $this->invalid('calculation_public_ids', 'The calculation belongs to a different customer.');
                }
                $serviceDate = data_get($calculation->getAttribute('input_snapshot'), 'service_date');
                if (! is_string($serviceDate) || $serviceDate < $data['period_from'] || $serviceDate > $data['period_until']) {
                    $this->invalid('calculation_public_ids', 'A calculation is outside the invoice period.');
                }
                if ($currency !== null && $currency !== $calculation->currency) {
                    $this->invalid('calculation_public_ids', 'All calculations must use the same currency.');
                }
                $serviceDates[(int) $calculation->id] = $serviceDate;
                $currency = $calculation->currency;
                if (BillingDocumentLine::query()->where('financial_calculation_id', $calculation->id)->exists()) {
                    $this->invalid('calculation_public_ids', 'A calculation is already linked to a billing document.');
                }
                $amount = $this->cents((string) $calculation->total_amount);
                if ($amount < 0 || $amount > 999999999999) {
                    $this->invalid('calculation_public_ids', 'A calculation exceeds the supported nonnegative line amount.');
                }
                $net += $amount;
            }
            $vat = $payer ? intdiv($net * $rateBasisPoints + 5000, 10000) : 0;
            $document = BillingDocument::query()->create([
                'owner_organization_id' => $ownerId,
                'counterparty_organization_id' => $customer->id,
                'document_type' => BillingDocument::TYPE_CUSTOMER_INVOICE,
                'period_from' => $data['period_from'], 'period_until' => $data['period_until'],
                'currency' => $currency,
                'vat_treatment' => $payer ? BillingDocument::VAT_STANDARD : BillingDocument::VAT_NOT_APPLICABLE,
                'vat_status_snapshot' => $profile->vat_status,
                'net_amount' => $this->money($net), 'vat_rate' => $payer ? $this->money($rateBasisPoints) : null,
                'vat_amount' => $this->money($vat), 'gross_amount' => $this->money($net + $vat),
                'status' => 'draft',
                'source_snapshot' => [
                    'source' => 'final_financial_calculations',
                    'calculation_public_ids' => $ids,
                    'tax_profile_id' => $profile->id,
                    'draft_idempotency_key' => $data['idempotency_key'],
                    'command_fingerprint' => $fingerprint,
                    'issuer' => $this->party($owner), 'customer' => $this->party($customer),
                ],
                'created_by_user_id' => $actor->id,
            ]);
            $allocatedVat = 0;
            $allocatedNet = 0;
            foreach ($calculations as $position => $calculation) {
                $lineNet = $this->cents((string) $calculation->total_amount);
                $allocatedNet += $lineNet;
                $nextVat = $payer ? intdiv($allocatedNet * $rateBasisPoints + 5000, 10000) : 0;
                $lineVat = $nextVat - $allocatedVat;
                $allocatedVat = $nextVat;
                BillingDocumentLine::query()->create([
                    'billing_document_id' => $document->id,
                    'financial_calculation_id' => $calculation->id,
                    'description' => 'Route service '.$serviceDates[(int) $calculation->id],
                    'quantity' => '1.000', 'unit_rate' => $this->money($lineNet),
                    'net_amount' => $this->money($lineNet), 'vat_amount' => $this->money($lineVat),
                    'gross_amount' => $this->money($lineNet + $lineVat),
                    'position' => $position + 1, 'created_at' => now(),
                ]);
            }

            return $this->present($document);
        });
    }

    /** @return array<string, mixed> */
    public function show(string $publicId): array
    {
        $organizationId = $this->organizationContext->requireId();
        $document = BillingDocument::query()->where('public_id', $publicId)
            ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
            ->where(static fn ($query) => $query->where('owner_organization_id', $organizationId)
                ->orWhere(static fn ($customerQuery) => $customerQuery
                    ->where('counterparty_organization_id', $organizationId)
                    ->whereIn('status', ['approved', 'closed'])))
            ->firstOrFail();

        return $this->present($document);
    }

    /** @return array<string, mixed> */
    private function present(BillingDocument $document): array
    {
        $document->load(['lines.financialCalculation:id,public_id', 'commercialIdentity']);
        $snapshot = $document->commercialIdentity?->getAttribute('counterparty_snapshot') ?? $document->getAttribute('source_snapshot');
        if (! is_array($snapshot)) {
            $snapshot = [];
        }
        $identity = $document->commercialIdentity;

        return [
            'public_id' => $document->public_id,
            'status' => $document->status, 'document_type' => $document->document_type,
            'period_from' => (string) $document->getRawOriginal('period_from'),
            'period_until' => (string) $document->getRawOriginal('period_until'),
            'currency' => $document->currency,
            'issuer' => $snapshot['issuer'] ?? null, 'customer' => $snapshot['customer'] ?? null,
            'document_number' => $identity?->document_number,
            'variable_symbol' => $identity?->variable_symbol,
            'issued_on' => $identity ? (string) $identity->getRawOriginal('issued_on') : null,
            'taxable_supply_on' => $identity && $identity->getRawOriginal('taxable_supply_on') !== null
                ? (string) $identity->getRawOriginal('taxable_supply_on') : null,
            'due_on' => $identity ? (string) $identity->getRawOriginal('due_on') : null,
            'net_amount' => $snapshot['net_amount'] ?? $document->net_amount, 'vat_amount' => $snapshot['vat_amount'] ?? $document->vat_amount,
            'gross_amount' => $snapshot['gross_amount'] ?? $document->gross_amount,
            'vat_rate' => $snapshot['vat_rate'] ?? $document->vat_rate,
            'lines' => $identity !== null ? ($snapshot['lines'] ?? []) : $document->lines->map(static fn (BillingDocumentLine $line): array => [
                'position' => $line->position, 'description' => $line->description,
                'quantity' => $line->quantity, 'unit_rate' => $line->unit_rate,
                'net_amount' => $line->net_amount, 'vat_amount' => $line->vat_amount,
                'gross_amount' => $line->gross_amount,
                'calculation_public_id' => $line->financialCalculation?->public_id,
            ])->all(),
            'draft' => $identity === null,
        ];
    }

    /** @return array<string, mixed> */
    private function party(Organization $organization): array
    {
        return [
            'name' => $organization->name, 'registration_number' => $organization->registration_number,
            'vat_number' => $organization->vat_number, 'street' => $organization->street,
            'city' => $organization->city, 'postal_code' => $organization->postal_code,
            'country_code' => $organization->country_code,
        ];
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
