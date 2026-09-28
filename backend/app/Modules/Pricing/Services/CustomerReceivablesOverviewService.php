<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\CustomerInvoiceBankPayment;
use Carbon\CarbonImmutable;

final class CustomerReceivablesOverviewService
{
    public function __construct(private readonly OrganizationContext $organizations) {}

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function overview(User $actor, array $filters): array
    {
        $organizationId = $this->organizations->requireId();
        abort_unless($actor->can('compensation.manage') && Organization::query()->whereKey($organizationId)->value('type') === Organization::TYPE_MASTER, 403);

        $date = (string) ($filters['as_of'] ?? now('Europe/Prague')->toDateString());
        $asOf = CarbonImmutable::parse($date, 'Europe/Prague')->startOfDay();
        $documents = BillingDocument::query()
            ->where('owner_organization_id', $organizationId)
            ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
            ->whereIn('status', ['approved', 'closed'])
            ->whereHas('commercialIdentity', static fn ($query) => $query->where('direction', BillingDocumentCommercialIdentity::DIRECTION_RECEIVABLE))
            ->with(['commercialIdentity', 'counterpartyOrganization:id,name'])
            ->orderBy('id')->get();
        $payments = CustomerInvoiceBankPayment::query()
            ->whereIn('billing_document_id', $documents->modelKeys())
            ->where('status', 'active')
            ->selectRaw('billing_document_id, SUM(allocated_amount_minor) as paid_minor')
            ->groupBy('billing_document_id')->pluck('paid_minor', 'billing_document_id');

        /** @var list<array{public_id: string, document_number: string, customer_organization_id: int, customer_name: string, issued_on: ?string, due_on: ?string, currency: string, invoice_amount_minor: int, paid_amount_minor: int, unpaid_amount_minor: int, payment_state: string, overdue_days: int}> $all */
        $all = [];
        /** @var array<int, array{id: int, name: string}> $customers */
        $customers = [];
        foreach ($documents as $document) {
            $identity = $document->commercialIdentity;
            if (! $identity instanceof BillingDocumentCommercialIdentity) {
                continue;
            }
            $gross = $this->minor((string) $document->gross_amount);
            $paid = (int) ($payments[$document->id] ?? 0);
            $unpaid = max(0, $gross - $paid);
            $rawDue = $identity->getRawOriginal('due_on');
            $dueDate = is_string($rawDue) ? substr($rawDue, 0, 10) : null;
            $rawIssued = $identity->getRawOriginal('issued_on');
            $issuedOn = is_string($rawIssued) ? substr($rawIssued, 0, 10) : null;
            $overdueDays = $unpaid > 0 && $dueDate !== null && $dueDate < $date
                ? (int) CarbonImmutable::parse($dueDate, 'Europe/Prague')->startOfDay()->diffInDays($asOf) : 0;
            $state = $unpaid === 0 ? 'paid' : ($paid > 0 ? 'partially_paid' : 'unpaid');
            $customerId = (int) $document->counterparty_organization_id;
            $customerName = (string) ($document->counterpartyOrganization->name ?? $identity->counterparty_name);
            $customers[$customerId] = ['id' => $customerId, 'name' => $customerName];
            $all[] = [
                'public_id' => (string) $document->public_id, 'document_number' => (string) $identity->document_number,
                'customer_organization_id' => $customerId, 'customer_name' => $customerName,
                'issued_on' => $issuedOn, 'due_on' => $dueDate,
                'currency' => (string) $document->currency, 'invoice_amount_minor' => $gross,
                'paid_amount_minor' => $paid, 'unpaid_amount_minor' => $unpaid,
                'payment_state' => $state, 'overdue_days' => $overdueDays,
            ];
        }

        $selectedCustomer = $filters['customer_organization_id'] ?? null;
        $selectedState = $filters['state'] ?? null;
        $filtered = array_values(array_filter($all, static function (array $item) use ($selectedCustomer, $selectedState): bool {
            if ($selectedCustomer !== null && $item['customer_organization_id'] !== (int) $selectedCustomer) {
                return false;
            }

            return match ($selectedState) {
                'open' => $item['unpaid_amount_minor'] > 0,
                'overdue' => $item['overdue_days'] > 0,
                'unpaid', 'partially_paid', 'paid' => $item['payment_state'] === $selectedState,
                default => true,
            };
        }));
        usort($filtered, static fn (array $a, array $b): int => strcmp($a['due_on'] ?? '9999-12-31', $b['due_on'] ?? '9999-12-31')
            ?: strcmp((string) $a['public_id'], (string) $b['public_id']));

        $groups = [];
        foreach ($filtered as $item) {
            $currency = (string) $item['currency'];
            $key = $item['customer_organization_id'].'|'.$currency;
            $groups[$key] ??= [
                'customer_organization_id' => $item['customer_organization_id'], 'customer_name' => $item['customer_name'],
                'currency' => $currency, 'invoice_amount_minor' => 0, 'paid_amount_minor' => 0,
                'unpaid_amount_minor' => 0, 'overdue_amount_minor' => 0, 'invoice_count' => 0,
            ];
            foreach (['invoice_amount_minor', 'paid_amount_minor', 'unpaid_amount_minor'] as $field) {
                $groups[$key][$field] += $item[$field];
            }
            $groups[$key]['overdue_amount_minor'] += $item['overdue_days'] > 0 ? $item['unpaid_amount_minor'] : 0;
            $groups[$key]['invoice_count']++;
        }
        $customerOptions = array_values($customers);
        usort($customerOptions, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = (int) ($filters['page'] ?? 1);
        $count = count($filtered);

        return [
            'as_of' => $date, 'balance_basis' => 'current_active_allocations',
            'customer_options' => $customerOptions,
            'customer_totals' => array_values($groups),
            'items' => array_slice($filtered, ($page - 1) * $perPage, $perPage),
            'pagination' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($count / $perPage)),
                'per_page' => $perPage, 'total' => $count],
        ];
    }

    private function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Invoice amount must have exactly two decimal places.');
        }

        return (int) $parts[1] * 100 + (int) $parts[2];
    }
}
