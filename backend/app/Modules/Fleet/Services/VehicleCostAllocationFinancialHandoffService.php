<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\VehicleCostAllocation;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffInstruction;
use App\Modules\Fleet\Models\VehicleCostAllocationLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationFinancialHandoffService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function prepare(string $allocationUid, int $expectedRevision, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($allocationUid, $expectedRevision, $organizationId, $actor): array {
            $allocation = VehicleCostAllocation::query()->where('allocation_uid', $allocationUid)->where('organization_context_id', $organizationId)->orderByDesc('revision')->lockForUpdate()->firstOrFail();
            if ((int) $allocation->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The allocation revision has changed.']]);
            }
            if ($allocation->status !== 'approved') {
                throw ValidationException::withMessages(['status' => ['Only an approved allocation can be handed off.']]);
            }
            $existing = VehicleCostAllocationFinancialHandoff::query()->where('vehicle_cost_allocation_id', $allocation->id)->where('allocation_revision', $expectedRevision)->first();
            if ($existing) {
                return $this->present($existing);
            }
            $handoff = VehicleCostAllocationFinancialHandoff::query()->create(['public_id' => (string) Str::uuid(), 'handoff_uid' => (string) Str::uuid(), 'vehicle_cost_allocation_id' => $allocation->id, 'allocation_uid' => $allocationUid, 'allocation_revision' => $expectedRevision, 'organization_context_id' => $organizationId, 'status' => 'prepared', 'net_amount' => $allocation->net_amount, 'vat_amount' => $allocation->vat_amount, 'gross_amount' => $allocation->gross_amount, 'currency' => $allocation->currency, 'prepared_by_user_id' => $actor->id, 'prepared_at' => now(), 'revision' => 1, 'financial_automation_performed' => false]);
            $sourceLines = VehicleCostAllocationLine::query()
                ->where('vehicle_cost_allocation_id', $allocation->getKey())
                ->orderBy('sequence_number')
                ->get();

            foreach ($sourceLines as $line) {
                $destination = $this->destination((string) $line->settlement_mode);
                VehicleCostAllocationFinancialHandoffInstruction::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_id' => $handoff->id, 'vehicle_cost_allocation_line_id' => $line->id, 'line_uid' => $line->line_uid, 'sequence_number' => $line->sequence_number, 'settlement_mode' => $line->settlement_mode, 'destination_type' => $destination, 'responsible_party_type' => $line->responsible_party_type, 'responsible_organization_id' => $line->responsible_organization_id, 'responsible_user_id' => $line->responsible_user_id, 'external_party_name' => $line->external_party_name, 'net_amount' => $line->net_amount, 'vat_amount' => $line->vat_amount, 'gross_amount' => $line->gross_amount, 'currency' => $line->currency, 'vat_treatment' => $line->vat_treatment, 'requires_invoice' => $line->settlement_mode === 'invoice_required', 'bank_matching_eligible' => false, 'execution_status' => 'pending', 'revision' => 1]);
            }
            VehicleCostAllocationFinancialHandoffEvent::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_id' => $handoff->id, 'event_type' => 'prepared', 'evidence' => ['financial_automation' => false, 'invoice_created' => false, 'payment_matched' => false], 'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now()]);

            return $this->present($handoff);
        });
    }

    public function show(string $allocationUid, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);
        $handoff = VehicleCostAllocationFinancialHandoff::query()->where('allocation_uid', $allocationUid)->where('organization_context_id', $organizationId)->latest('id')->firstOrFail();

        return $this->present($handoff);
    }

    private function authorize(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403);
        }$this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function destination(string $mode): string
    {
        return match ($mode) {
            'invoice_required' => 'billing_document','deposit_offset' => 'settlement_deduction','repair_fund_reserve' => 'repair_fund','insurance_recovery','state_recovery' => 'receivable_tracking','informational_only' => 'information','manual_review' => 'manual_review',default => throw ValidationException::withMessages(['settlement_mode' => ['Unsupported settlement mode.']])
        };
    }

    private function present(VehicleCostAllocationFinancialHandoff $handoff): array
    {
        $handoff->load(['instructions' => fn ($q) => $q->orderBy('sequence_number'), 'events' => fn ($q) => $q->orderBy('revision')]);

        return ['handoff_uid' => $handoff->handoff_uid, 'allocation_uid' => $handoff->allocation_uid, 'allocation_revision' => (int) $handoff->allocation_revision, 'status' => $handoff->status, 'net_amount' => $handoff->net_amount, 'vat_amount' => $handoff->vat_amount, 'gross_amount' => $handoff->gross_amount, 'currency' => $handoff->currency, 'instructions' => $handoff->instructions->toArray(), 'events' => $handoff->events->toArray(), 'financial_automation_performed' => false, 'invoice_created' => false, 'payment_matched' => false];
    }
}
