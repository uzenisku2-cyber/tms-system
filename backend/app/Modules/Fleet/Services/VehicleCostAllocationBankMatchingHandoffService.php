<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationBankMatchingHandoffEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffExecution;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffInstruction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationBankMatchingHandoffService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function prepare(string $instructionPublicId, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($instructionPublicId, $data, $organizationId, $actor): array {
            $instruction = VehicleCostAllocationFinancialHandoffInstruction::query()->where('public_id', $instructionPublicId)->lockForUpdate()->firstOrFail();
            $handoff = VehicleCostAllocationFinancialHandoff::query()->findOrFail((int) $instruction->financial_handoff_id);
            if ((int) $handoff->organization_context_id !== $organizationId) {
                abort(404);
            }if ((int) $instruction->revision !== (int) $data['expected_instruction_revision']) {
                throw ValidationException::withMessages(['expected_instruction_revision' => ['The instruction revision has changed.']]);
            }if ($instruction->destination_type !== 'billing_document' || ! $instruction->requires_invoice) {
                throw ValidationException::withMessages(['instruction' => ['Only a billing-document instruction can prepare bank matching evidence.']]);
            }if (! VehicleCostAllocationFinancialHandoffExecution::query()->where('financial_handoff_instruction_id', $instruction->id)->exists()) {
                throw ValidationException::withMessages(['instruction' => ['A billing document handoff execution is required.']]);
            }$this->responsibleParty($instruction, $organizationId, $actor);
            $byKey = VehicleCostAllocationBankMatchingHandoff::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($byKey) {
                if ((int) $byKey->financial_handoff_instruction_id !== (int) $instruction->id) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another instruction.']]);
                }

                return $this->present($byKey);
            }if (VehicleCostAllocationBankMatchingHandoff::query()->where('financial_handoff_instruction_id', $instruction->id)->where('bank_transaction_reference', $data['bank_transaction_reference'])->exists()) {
                throw ValidationException::withMessages(['bank_transaction_reference' => ['This bank evidence is already prepared for the instruction.']]);
            }$execution = VehicleCostAllocationFinancialHandoffExecution::query()->where('financial_handoff_instruction_id', $instruction->id)->firstOrFail();
            $record = VehicleCostAllocationBankMatchingHandoff::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_instruction_id' => $instruction->id, 'billing_document_id' => $execution->billing_document_id, 'organization_context_id' => $organizationId, 'idempotency_key' => $data['idempotency_key'], 'instruction_revision' => (int) $instruction->revision, 'responsible_party_type' => $instruction->responsible_party_type, 'responsible_organization_id' => $instruction->responsible_organization_id, 'responsible_user_id' => $instruction->responsible_user_id, 'bank_transaction_reference' => $data['bank_transaction_reference'], 'bank_statement_reference' => $data['bank_statement_reference'] ?? null, 'booked_at' => $data['booked_at'], 'evidence_amount' => $data['evidence_amount'], 'currency' => strtoupper($data['currency']), 'counterparty_name' => $data['counterparty_name'] ?? null, 'evidence_note' => $data['evidence_note'], 'status' => 'prepared', 'prepared_by_user_id' => $actor->id, 'prepared_at' => now(), 'revision' => 1]);
            VehicleCostAllocationBankMatchingHandoffEvent::query()->create(['public_id' => (string) Str::uuid(), 'bank_matching_handoff_id' => $record->id, 'event_type' => 'bank_matching_evidence_prepared', 'evidence' => ['instruction_public_id' => $instructionPublicId, 'bank_transaction_reference' => $data['bank_transaction_reference'], 'bank_matching_performed' => false, 'payment_marked' => false, 'invoice_created' => false, 'deposit_offset_performed' => false, 'repair_fund_movement_performed' => false], 'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now()]);

            return $this->present($record);
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

    private function present(VehicleCostAllocationBankMatchingHandoff $record): array
    {
        $events = VehicleCostAllocationBankMatchingHandoffEvent::query()->where('bank_matching_handoff_id', $record->id)->orderBy('revision')->get();

        return ['handoff_public_id' => $record->public_id, 'status' => $record->status, 'bank_transaction_reference' => $record->bank_transaction_reference, 'evidence_amount' => $record->evidence_amount, 'currency' => $record->currency, 'events' => $events->toArray(), 'bank_matching_performed' => false, 'payment_marked' => false, 'invoice_created' => false, 'deposit_offset_performed' => false, 'repair_fund_movement_performed' => false];
    }
}
