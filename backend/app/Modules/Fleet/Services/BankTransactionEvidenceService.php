<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Models\BankTransactionEvidenceEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BankTransactionEvidenceService
{
    public function record(array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);

        return DB::transaction(function () use ($data, $organizationId, $actor): array {
            $byKey = BankTransactionEvidence::query()
                ->where('organization_context_id', $organizationId)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($byKey instanceof BankTransactionEvidence) {
                return $this->present($byKey);
            }

            if (BankTransactionEvidence::query()->where('organization_context_id', $organizationId)->where('source_type', $data['source_type'])->where('source_reference', $data['source_reference'])->exists()) {
                throw ValidationException::withMessages(['source_reference' => ['This bank transaction evidence already exists in the organization context.']]);
            }

            $record = BankTransactionEvidence::query()->create([
                'public_id' => (string) Str::uuid(),
                'organization_context_id' => $organizationId,
                'idempotency_key' => $data['idempotency_key'],
                'source_type' => $data['source_type'],
                'source_reference' => $data['source_reference'],
                'bank_statement_reference' => $data['bank_statement_reference'] ?? null,
                'direction' => $data['direction'],
                'booked_at' => $data['booked_at'],
                'value_date' => $data['value_date'] ?? null,
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency']),
                'account_identifier' => $data['account_identifier'] ?? null,
                'counterparty_name' => $data['counterparty_name'] ?? null,
                'counterparty_account_identifier' => $data['counterparty_account_identifier'] ?? null,
                'variable_symbol' => $data['variable_symbol'] ?? null,
                'message' => $data['message'] ?? null,
                'evidence_note' => $data['evidence_note'],
                'status' => 'recorded',
                'recorded_by_user_id' => $actor->id,
                'recorded_at' => now(),
                'revision' => 1,
            ]);

            BankTransactionEvidenceEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'bank_transaction_evidence_id' => $record->id,
                'event_type' => 'bank_transaction_evidence_recorded',
                'evidence' => [
                    'source_type' => $record->source_type,
                    'source_reference' => $record->source_reference,
                    'bank_matching_performed' => false,
                    'payment_marked' => false,
                    'invoice_modified' => false,
                    'deposit_offset_performed' => false,
                    'repair_fund_movement_performed' => false,
                ],
                'actor_user_id' => $actor->id,
                'revision' => 1,
                'occurred_at' => now(),
            ]);

            return $this->present($record);
        });
    }

    private function present(BankTransactionEvidence $record): array
    {
        $events = BankTransactionEvidenceEvent::query()->where('bank_transaction_evidence_id', $record->id)->orderBy('revision')->get();

        return [
            'bank_transaction_evidence_public_id' => $record->public_id,
            'status' => $record->status,
            'source_reference' => $record->source_reference,
            'direction' => $record->direction,
            'amount' => $record->amount,
            'currency' => $record->currency,
            'events' => $events->toArray(),
            'bank_matching_performed' => false,
            'payment_marked' => false,
            'invoice_modified' => false,
            'deposit_offset_performed' => false,
            'repair_fund_movement_performed' => false,
        ];
    }
}
