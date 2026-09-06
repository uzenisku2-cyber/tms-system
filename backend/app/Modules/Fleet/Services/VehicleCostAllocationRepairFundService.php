<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffInstruction;
use App\Modules\Fleet\Models\VehicleCostAllocationRepairFundEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationRepairFundReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationRepairFundService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function reserve(string $instructionPublicId, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($instructionPublicId, $data, $organizationId, $actor): array {
            $instruction = VehicleCostAllocationFinancialHandoffInstruction::query()->where('public_id', $instructionPublicId)->lockForUpdate()->firstOrFail();
            $handoff = VehicleCostAllocationFinancialHandoff::query()->findOrFail((int) $instruction->financial_handoff_id);
            if ((int) $handoff->organization_context_id !== $organizationId) {
                abort(404);
            }
            if ((int) $instruction->revision !== (int) $data['expected_instruction_revision']) {
                throw ValidationException::withMessages(['expected_instruction_revision' => ['The instruction revision has changed.']]);
            }
            if ($instruction->destination_type !== 'repair_fund' || $instruction->settlement_mode !== 'repair_fund_reserve' || $instruction->requires_invoice || $instruction->bank_matching_eligible) {
                throw ValidationException::withMessages(['instruction' => ['Only a non-executing repair-fund reserve instruction can be reserved.']]);
            }
            $this->responsibleParty($instruction, $organizationId, $actor);
            $byKey = VehicleCostAllocationRepairFundReservation::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($byKey) {
                if ((int) $byKey->financial_handoff_instruction_id !== (int) $instruction->id) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another instruction.']]);
                }

                return $this->present($byKey);
            }
            if (VehicleCostAllocationRepairFundReservation::query()->where('financial_handoff_instruction_id', $instruction->id)->exists()) {
                throw ValidationException::withMessages(['instruction' => ['The instruction was already reserved with another idempotency key.']]);
            }
            $reservation = VehicleCostAllocationRepairFundReservation::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_instruction_id' => $instruction->id, 'organization_context_id' => $organizationId, 'idempotency_key' => $data['idempotency_key'], 'instruction_revision' => (int) $instruction->revision, 'responsible_party_type' => $instruction->responsible_party_type, 'responsible_organization_id' => $instruction->responsible_organization_id, 'responsible_user_id' => $instruction->responsible_user_id, 'net_amount' => $instruction->net_amount, 'vat_amount' => $instruction->vat_amount, 'gross_amount' => $instruction->gross_amount, 'currency' => $instruction->currency, 'reserve_purpose' => $data['reserve_purpose'], 'evidence_note' => $data['evidence_note'], 'status' => 'reserved', 'reserved_by_user_id' => $actor->id, 'reserved_at' => now(), 'revision' => 1]);
            VehicleCostAllocationRepairFundEvent::query()->create(['public_id' => (string) Str::uuid(), 'repair_fund_reservation_id' => $reservation->id, 'event_type' => 'repair_fund_reserved', 'evidence' => ['instruction_public_id' => $instructionPublicId, 'reserve_purpose' => $data['reserve_purpose'], 'invoice_created' => false, 'billing_document_created' => false, 'bank_transaction_created' => false, 'bank_transaction_matched' => false, 'payment_marked' => false, 'fund_movement_created' => false, 'settlement_deduction_applied' => false], 'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now()]);

            return $this->present($reservation);
        });
    }

    private function authorize(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403);
        } $this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function responsibleParty(VehicleCostAllocationFinancialHandoffInstruction $instruction, int $organizationId, User $actor): void
    {
        if ($instruction->responsible_party_type === 'organization' && $instruction->responsible_organization_id) {
            $this->authorization->findManageableOrganization($actor, $organizationId, (int) $instruction->responsible_organization_id);

            return;
        }
        if ($instruction->responsible_party_type === 'driver' && $instruction->responsible_user_id) {
            $driver = Driver::query()->where('user_id', $instruction->responsible_user_id)->firstOrFail();
            $this->authorization->findVisibleDriver($actor, $organizationId, (int) $driver->id);

            return;
        }
        throw ValidationException::withMessages(['responsible_party_type' => ['A registered organization or driver is required.']]);
    }

    private function present(VehicleCostAllocationRepairFundReservation $reservation): array
    {
        $events = VehicleCostAllocationRepairFundEvent::query()->where('repair_fund_reservation_id', $reservation->id)->orderBy('revision')->get();

        return ['reservation_public_id' => $reservation->public_id, 'status' => $reservation->status, 'net_amount' => $reservation->net_amount, 'vat_amount' => $reservation->vat_amount, 'gross_amount' => $reservation->gross_amount, 'currency' => $reservation->currency, 'reserve_purpose' => $reservation->reserve_purpose, 'events' => $events->toArray(), 'invoice_created' => false, 'billing_document_created' => false, 'bank_transaction_created' => false, 'bank_transaction_matched' => false, 'payment_marked' => false, 'fund_movement_created' => false, 'settlement_deduction_applied' => false];
    }
}
