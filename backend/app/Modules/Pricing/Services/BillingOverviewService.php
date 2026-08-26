<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class BillingOverviewService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(User $actor, array $filters): array
    {
        $organizationId = $this->organizationContext->requireId();
        $organization = Organization::query()->findOrFail($organizationId);
        $companyView = $organization->getAttribute('type') === Organization::TYPE_MASTER
            && $actor->can('compensation.manage');

        $driverId = Driver::query()
            ->where('user_id', $actor->getKey())
            ->value('id');

        $base = BillingDocument::query()
            ->with([
                'counterpartyOrganization:id,name,vat_status',
                'driver:id,first_name,last_name',
            ]);

        if ($companyView) {
            $base->where('owner_organization_id', $organizationId);
        } else {
            $base->where(static function (Builder $scope) use ($organizationId, $driverId): void {
                $scope->where('counterparty_organization_id', $organizationId);

                if (is_int($driverId) && $driverId > 0) {
                    $scope->orWhere('driver_id', $driverId);
                }
            });
        }

        $availablePeriods = $this->availablePeriods(
            (clone $base)->get(['period_from']),
        );

        $periodFrom = $filters['period_from'] ?? null;
        $periodUntil = $filters['period_until'] ?? null;
        $documentType = $filters['document_type'] ?? null;

        if (is_string($periodFrom) && $periodFrom !== '') {
            $base->whereDate('period_until', '>=', $periodFrom);
        }

        if (is_string($periodUntil) && $periodUntil !== '') {
            $base->whereDate('period_from', '<=', $periodUntil);
        }

        if (is_string($documentType) && $documentType !== '') {
            $base->where('document_type', $documentType);
        }

        $summaryDocuments = (clone $base)->get([
            'document_type',
            'net_amount',
            'vat_amount',
            'gross_amount',
        ]);

        $perPage = is_int($filters['per_page'] ?? null)
            ? (int) $filters['per_page']
            : 25;

        $documents = $base
            ->orderByDesc('period_from')
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'visibility' => $companyView ? 'company' : 'own',
            'vat_breakdown_visible' => $companyView,
            'margin_visible' => $companyView,
            'available_periods' => $availablePeriods,
            'summary' => $companyView
                ? $this->companySummary($summaryDocuments->all())
                : null,
            'items' => array_map(
                fn (BillingDocument $document): array => $this->document(
                    $document,
                    $companyView,
                ),
                $documents->items(),
            ),
            'pagination' => $this->pagination($documents),
        ];
    }

    /**
     * @param  iterable<int, BillingDocument>  $documents
     * @return array{years: list<int>, months: list<string>}
     */
    private function availablePeriods(iterable $documents): array
    {
        $months = [];

        foreach ($documents as $document) {
            $periodFrom = $document->getAttribute('period_from');

            if ($periodFrom === null) {
                continue;
            }

            $months[$periodFrom->format('Y-m')] = true;
        }

        $monthValues = array_keys($months);
        rsort($monthValues, SORT_STRING);

        $years = [];

        foreach ($monthValues as $monthValue) {
            $years[(int) substr($monthValue, 0, 4)] = true;
        }

        $yearValues = array_keys($years);
        rsort($yearValues, SORT_NUMERIC);

        return [
            'years' => $yearValues,
            'months' => $monthValues,
        ];
    }

    /** @param  list<BillingDocument>  $documents */
    private function companySummary(array $documents): array
    {
        $revenue = $this->totals($documents, BillingDocument::TYPE_CUSTOMER_INVOICE);
        $carrier = $this->totals($documents, BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT);
        $driver = $this->totals($documents, BillingDocument::TYPE_DRIVER_REMUNERATION);
        $marginCents = $this->cents($revenue['net'])
            - $this->cents($carrier['net'])
            - $this->cents($driver['net']);

        return [
            'customer_billing' => $revenue,
            'external_carrier_cost' => $carrier,
            'driver_cost' => $driver,
            'gross_margin_net' => $this->money($marginCents),
        ];
    }

    /**
     * @param  list<BillingDocument>  $documents
     * @return array{net: string, vat: string, gross: string}
     */
    private function totals(array $documents, string $type): array
    {
        $net = 0;
        $vat = 0;
        $gross = 0;

        foreach ($documents as $document) {
            if ($document->getAttribute('document_type') !== $type) {
                continue;
            }

            $net += $this->cents($document->getAttribute('net_amount'));
            $vat += $this->cents($document->getAttribute('vat_amount'));
            $gross += $this->cents($document->getAttribute('gross_amount'));
        }

        return [
            'net' => $this->money($net),
            'vat' => $this->money($vat),
            'gross' => $this->money($gross),
        ];
    }

    /** @return array<string, mixed> */
    private function document(BillingDocument $document, bool $companyView): array
    {
        $counterparty = $document->getRelation('counterpartyOrganization');
        $driver = $document->getRelation('driver');
        $item = [
            'public_id' => (string) $document->getAttribute('public_id'),
            'document_type' => (string) $document->getAttribute('document_type'),
            'status' => (string) $document->getAttribute('status'),
            'period_from' => $document->getAttribute('period_from')?->toDateString(),
            'period_until' => $document->getAttribute('period_until')?->toDateString(),
            'currency' => (string) $document->getAttribute('currency'),
            'counterparty_name' => $counterparty instanceof Organization
                ? (string) $counterparty->getAttribute('name')
                : null,
            'driver_name' => $driver instanceof Driver
                ? trim((string) $driver->getAttribute('first_name').' '.(string) $driver->getAttribute('last_name'))
                : null,
        ];

        if ($companyView) {
            return array_merge($item, [
                'vat_treatment' => (string) $document->getAttribute('vat_treatment'),
                'net_amount' => (string) $document->getAttribute('net_amount'),
                'vat_rate' => $document->getAttribute('vat_rate'),
                'vat_amount' => (string) $document->getAttribute('vat_amount'),
                'gross_amount' => (string) $document->getAttribute('gross_amount'),
            ]);
        }

        $amount = $document->getAttribute('vat_treatment') === BillingDocument::VAT_NOT_APPLICABLE
            ? $document->getAttribute('net_amount')
            : $document->getAttribute('gross_amount');

        return array_merge($item, [
            'amount' => (string) $amount,
        ]);
    }

    private function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /** @return array<string, int> */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
