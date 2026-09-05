<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoff;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffExecution;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffExecutionEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationFinancialHandoffInstruction;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationBillingDocumentHandoffService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function execute(string $instructionPublicId, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);

        return DB::transaction(function () use ($instructionPublicId, $data, $organizationId, $actor): array {
            $instruction = VehicleCostAllocationFinancialHandoffInstruction::query()->where('public_id', $instructionPublicId)->lockForUpdate()->firstOrFail();
            $handoff = VehicleCostAllocationFinancialHandoff::query()->findOrFail((int) $instruction->financial_handoff_id);
            if ((int) $handoff->organization_context_id !== $organizationId) {
                abort(404);
            }if ((int) $instruction->revision !== (int) $data['expected_instruction_revision']) {
                throw ValidationException::withMessages(['expected_instruction_revision' => ['The instruction revision has changed.']]);
            }if ($instruction->destination_type !== 'billing_document' || ! $instruction->requires_invoice || $instruction->settlement_mode !== 'invoice_required') {
                throw ValidationException::withMessages(['instruction' => ['Only an invoice-required billing-document instruction can be executed here.']]);
            }
            $byKey = VehicleCostAllocationFinancialHandoffExecution::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($byKey) {
                if ((int) $byKey->financial_handoff_instruction_id !== (int) $instruction->id) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key is already used for another instruction.']]);
                }

                return $this->present($byKey);
            }$existing = VehicleCostAllocationFinancialHandoffExecution::query()->where('financial_handoff_instruction_id', $instruction->id)->first();
            if ($existing) {
                throw ValidationException::withMessages(['instruction' => ['The instruction was already executed with another idempotency key.']]);
            }
            [$counterpartyOrganizationId,$driverId] = $this->counterparty($instruction, $organizationId, $actor);
            $basisPoints = (int) $data['vat_rate_basis_points'];
            if ($instruction->vat_treatment !== 'standard_rate') {
                throw ValidationException::withMessages(['vat_treatment' => ['An invoice requires standard-rate VAT treatment.']]);
            }$profile = DB::table('organization_tax_profiles')->where('organization_id', $organizationId)->where('valid_from', '<=', $data['period_until'])->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $data['period_from']))->orderByDesc('valid_from')->first();
            if (! $profile || $profile->vat_status !== 'payer' || (int) round((float) $profile->vat_rate * 100) !== $basisPoints) {
                throw ValidationException::withMessages(['vat_rate_basis_points' => ['VAT rate does not match the active payer tax profile.']]);
            }$net = $this->cents((string) $instruction->net_amount);
            $vat = $this->cents((string) $instruction->vat_amount);
            if ((int) round($net * $basisPoints / 10000) !== $vat) {
                throw ValidationException::withMessages(['vat_amount' => ['VAT amount does not match net amount and VAT rate.']]);
            }
            $document = BillingDocument::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'counterparty_organization_id' => $counterpartyOrganizationId, 'driver_id' => $driverId, 'document_type' => BillingDocument::TYPE_CUSTOMER_INVOICE, 'period_from' => $data['period_from'], 'period_until' => $data['period_until'], 'currency' => $instruction->currency, 'vat_treatment' => BillingDocument::VAT_STANDARD, 'vat_status_snapshot' => 'payer', 'net_amount' => $instruction->net_amount, 'vat_rate' => number_format($basisPoints / 100, 2, '.', ''), 'vat_amount' => $instruction->vat_amount, 'gross_amount' => $instruction->gross_amount, 'status' => 'draft', 'source_snapshot' => ['source' => 'vehicle_cost_allocation_financial_handoff', 'instruction_public_id' => $instructionPublicId, 'instruction_revision' => (int) $instruction->revision, 'handoff_uid' => $handoff->handoff_uid, 'vat_profile_id' => $profile->id, 'bank_matching_performed' => false, 'deposit_offset_performed' => false, 'repair_fund_movement_performed' => false], 'created_by_user_id' => $actor->id]);
            BillingDocumentLine::query()->create(['billing_document_id' => $document->id, 'financial_calculation_id' => null, 'description' => $data['description'], 'quantity' => '1.000', 'unit_rate' => $instruction->net_amount, 'net_amount' => $instruction->net_amount, 'vat_amount' => $instruction->vat_amount, 'gross_amount' => $instruction->gross_amount, 'position' => 1, 'created_at' => now()]);
            $execution = VehicleCostAllocationFinancialHandoffExecution::query()->create(['public_id' => (string) Str::uuid(), 'financial_handoff_instruction_id' => $instruction->id, 'billing_document_id' => $document->id, 'organization_context_id' => $organizationId, 'idempotency_key' => $data['idempotency_key'], 'instruction_revision' => (int) $instruction->revision, 'status' => 'executed', 'net_amount' => $instruction->net_amount, 'vat_amount' => $instruction->vat_amount, 'gross_amount' => $instruction->gross_amount, 'currency' => $instruction->currency, 'vat_rate_basis_points' => $basisPoints, 'executed_by_user_id' => $actor->id, 'executed_at' => now(), 'revision' => 1]);
            VehicleCostAllocationFinancialHandoffExecutionEvent::query()->create(['public_id' => (string) Str::uuid(), 'handoff_execution_id' => $execution->id, 'event_type' => 'billing_document_created', 'evidence' => ['billing_document_public_id' => $document->public_id, 'billing_document_status' => 'draft', 'bank_matching_performed' => false, 'payment_marked' => false, 'deposit_offset_performed' => false, 'repair_fund_movement_performed' => false], 'actor_user_id' => $actor->id, 'revision' => 1, 'occurred_at' => now()]);

            return $this->present($execution);
        });
    }

    private function authorize(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403);
        }$this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function counterparty(VehicleCostAllocationFinancialHandoffInstruction $instruction, int $organizationId, User $actor): array
    {
        if ($instruction->responsible_party_type === 'organization' && $instruction->responsible_organization_id) {
            $organization = $this->authorization->findManageableOrganization($actor, $organizationId, (int) $instruction->responsible_organization_id);

            return [$organization->id, null];
        }if ($instruction->responsible_party_type === 'driver' && $instruction->responsible_user_id) {
            $driver = Driver::query()->where('user_id', $instruction->responsible_user_id)->firstOrFail();
            $driver = $this->authorization->findVisibleDriver($actor, $organizationId, (int) $driver->id);

            return [null, $driver->id];
        }throw ValidationException::withMessages(['responsible_party_type' => ['Invoice execution requires a registered organization or driver counterparty.']]);
    }

    private function cents(string $amount): int
    {
        [$whole,$fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function present(VehicleCostAllocationFinancialHandoffExecution $execution): array
    {
        $instruction = VehicleCostAllocationFinancialHandoffInstruction::query()->findOrFail((int) $execution->financial_handoff_instruction_id);
        $document = BillingDocument::query()->with('lines')->findOrFail((int) $execution->billing_document_id);
        $events = VehicleCostAllocationFinancialHandoffExecutionEvent::query()->where('handoff_execution_id', $execution->id)->orderBy('revision')->get();

        return ['execution_public_id' => $execution->public_id, 'instruction_public_id' => $instruction->public_id, 'billing_document' => $document->toArray(), 'events' => $events->toArray(), 'bank_matching_performed' => false, 'payment_marked' => false, 'deposit_offset_performed' => false, 'repair_fund_movement_performed' => false];
    }
}
