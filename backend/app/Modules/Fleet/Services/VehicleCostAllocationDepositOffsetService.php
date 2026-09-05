<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\VehicleCostAllocationDepositOffsetAcknowledgement;
use App\Modules\Fleet\Models\VehicleCostAllocationDepositOffsetEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffInstruction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationDepositOffsetService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function acknowledge(string $instructionPublicId, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($instructionPublicId, $data, $organizationId, $actor): array {
            $instruction = VehicleCostAllocationFinancialHandoffInstruction::query()->where('public_id', $instructionPublicId)->lockForUpdate()->firstOrFail();
            $handoff = VehicleCostAllocationFinancialHandoff::query()->findOrFail((int) $instruction->financial_handoff_id);
            if ((int) $handoff->organization_context_id !== $organizationId) {
                abort(404);
            }if ((int) $instruction->revision !== (int) $data['expected_instruction_revision']) {
                throw ValidationException::withMessages(['expected_instruction_revision' => ['The instruction revision has changed.']]);
            }if ($instruction->destination_type !== 'settlement_deduction' || $instruction->settlement_mode !== 'deposit_offset' || $instruction->requires_invoice) {
                throw ValidationException::withMessages(['instruction' => ['Only a no-invoice deposit-offset instruction can be acknowledged.']]);
            }$this->responsibleParty($instruction, $organizationId, $actor);
            $byKey = VehicleCostAllocationDepositOffsetAcknowledgement::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($byKey) {
                if ((int) $byKey->financial_handoff_instruction_id !== (int) $instruction->id) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another instruction.']]);
                }

                return $this->present($byKey);
            }$existing = VehicleCostAllocationDepositOffsetAcknowledgement::query()->where('financial_handoff_instruction_id', $instruction->id)->first();
            if ($existing) {
                throw ValidationException::withMessages(['instruction' => ['The instruction was already acknowledged with another idempotency key.']]);
            }$ack = VehicleCostAllocationDepositOffsetAcknowledgement::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_instruction_id' => $instruction->id, 'organization_context_id' => $organizationId, 'idempotency_key' => $data['idempotency_key'], 'instruction_revision' => (int) $instruction->revision, 'responsible_party_type' => $instruction->responsible_party_type, 'responsible_organization_id' => $instruction->responsible_organization_id, 'responsible_user_id' => $instruction->responsible_user_id, 'net_amount' => $instruction->net_amount, 'vat_amount' => $instruction->vat_amount, 'gross_amount' => $instruction->gross_amount, 'currency' => $instruction->currency, 'payment_method' => $data['payment_method'], 'payment_reference' => $data['payment_reference'] ?? null, 'evidence_note' => $data['evidence_note'], 'vat_disposition' => $data['vat_disposition'], 'status' => 'acknowledged', 'acknowledged_by_user_id' => $actor->id, 'acknowledged_at' => now(), 'revision' => 1]);
            VehicleCostAllocationDepositOffsetEvent::query()->create(['public_id' => (string) Str::uuid(), 'deposit_offset_acknowledgement_id' => $ack->id, 'event_type' => 'paid_advance_acknowledged', 'evidence' => ['instruction_public_id' => $instructionPublicId, 'payment_method' => $data['payment_method'], 'payment_reference' => $data['payment_reference'] ?? null, 'vat_disposition' => $data['vat_disposition'], 'invoice_created' => false, 'billing_document_created' => false, 'bank_transaction_matched' => false, 'payment_marked' => false, 'repair_fund_movement_created' => false, 'settlement_deduction_applied' => false], 'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now()]);

            return $this->present($ack);
        });
    }

    private function authorize(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403);
        }$this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function responsibleParty(VehicleCostAllocationFinancialHandoffInstruction $instruction, int $organizationId, User $actor): void
    {
        if ($instruction->responsible_party_type === 'organization' && $instruction->responsible_organization_id) {
            $this->authorization->findManageableOrganization($actor, $organizationId, (int) $instruction->responsible_organization_id);

            return;
        }if ($instruction->responsible_party_type === 'driver' && $instruction->responsible_user_id) {
            $driver = Driver::query()->where('user_id', $instruction->responsible_user_id)->firstOrFail();
            $this->authorization->findVisibleDriver($actor, $organizationId, (int) $driver->id);

            return;
        }throw ValidationException::withMessages(['responsible_party_type' => ['A registered organization or driver is required.']]);
    }

    private function present(VehicleCostAllocationDepositOffsetAcknowledgement $ack): array
    {
        $events = VehicleCostAllocationDepositOffsetEvent::query()->where('deposit_offset_acknowledgement_id', $ack->id)->orderBy('revision')->get();

        return ['acknowledgement_public_id' => $ack->public_id, 'status' => $ack->status, 'net_amount' => $ack->net_amount, 'vat_amount' => $ack->vat_amount, 'gross_amount' => $ack->gross_amount, 'currency' => $ack->currency, 'vat_disposition' => $ack->vat_disposition, 'events' => $events->toArray(), 'invoice_created' => false, 'billing_document_created' => false, 'bank_transaction_matched' => false, 'payment_marked' => false, 'repair_fund_movement_created' => false, 'settlement_deduction_applied' => false];
    }
}
