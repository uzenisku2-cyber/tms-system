<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialMutualCharge;
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
                ->map(fn (FinancialSettlementStatement $statement): array => $this->presentStatement($statement))
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

        return $this->presentStatement($statement);
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
    private function presentStatement(FinancialSettlementStatement $statement): array
    {
        $statement->loadMissing(['lines', 'events', 'billingDocument.commercialIdentity', 'billingDocument.lines']);

        return [
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
            'revision' => (int) $statement->revision,
            'lines' => $statement->lines->toArray(),
            'events' => $statement->events->toArray(),
            'output_kind' => $statement->output_kind,
            'output_direction' => $statement->output_direction,
            'billing_document' => $statement->billingDocument?->toArray(),
            'billing_document_created' => $statement->billing_document_id !== null,
            'payment_marked' => false,
            'bank_matching_performed' => false,
        ];
    }
}
