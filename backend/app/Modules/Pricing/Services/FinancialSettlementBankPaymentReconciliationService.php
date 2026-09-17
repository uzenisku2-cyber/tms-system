<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialSettlementBankPayment;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliation;
use App\Modules\Pricing\Models\FinancialSettlementBankPaymentReconciliationEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementBankPaymentReconciliationService
{
    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool, created: bool} */
    public function confirm(string $statementPublicId, string $paymentPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = $this->fingerprint('confirm', $statementPublicId, $paymentPublicId, $organizationId, $data);

        return DB::transaction(function () use ($statementPublicId, $paymentPublicId, $data, $organizationId, $actor, $fingerprint): array {
            $replay = $this->replay((string) $data['idempotency_key'], $fingerprint, $organizationId);
            if ($replay instanceof FinancialSettlementBankPaymentReconciliation) {
                return ['data' => $this->present($replay), 'replayed' => true, 'created' => false];
            }

            $payment = $this->payment($statementPublicId, $paymentPublicId, $organizationId);
            if ($payment->status !== FinancialSettlementBankPayment::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['payment' => ['Only an active settlement bank payment may be reconciled.']]);
            }
            if ((int) $payment->revision !== (int) $data['expected_payment_revision']) {
                throw ValidationException::withMessages(['expected_payment_revision' => ['The settlement bank payment revision is stale.']]);
            }

            $reconciliation = FinancialSettlementBankPaymentReconciliation::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_bank_payment_id', $payment->id)
                ->lockForUpdate()
                ->first();
            $currentRevision = $reconciliation instanceof FinancialSettlementBankPaymentReconciliation ? (int) $reconciliation->revision : 0;
            if ($currentRevision !== (int) $data['expected_reconciliation_revision']) {
                throw ValidationException::withMessages(['expected_reconciliation_revision' => ['The reconciliation revision is stale.']]);
            }
            if ($reconciliation instanceof FinancialSettlementBankPaymentReconciliation
                && $reconciliation->status !== FinancialSettlementBankPaymentReconciliation::STATUS_OPEN) {
                throw ValidationException::withMessages(['reconciliation' => ['Only an open reconciliation may be confirmed.']]);
            }

            $created = ! $reconciliation instanceof FinancialSettlementBankPaymentReconciliation;
            $now = now();
            if ($created) {
                $reconciliation = FinancialSettlementBankPaymentReconciliation::query()->create([
                    'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                    'financial_settlement_bank_payment_id' => $payment->id,
                    'financial_settlement_statement_id' => $payment->financial_settlement_statement_id,
                    'bank_transaction_evidence_id' => $payment->bank_transaction_evidence_id,
                    'payment_revision' => $payment->revision, 'status' => FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED,
                    'revision' => 1, 'reason' => $data['reason'], 'confirmed_by_user_id' => $actor->id,
                    'confirmed_at' => $now,
                ]);
                $fromStatus = null;
            } else {
                $fromStatus = $reconciliation->status;
                $reconciliation->fill([
                    'payment_revision' => $payment->revision, 'status' => FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED,
                    'revision' => $currentRevision + 1, 'reason' => $data['reason'],
                    'confirmed_by_user_id' => $actor->id, 'confirmed_at' => $now,
                    'reopened_by_user_id' => null, 'reopened_at' => null,
                ])->save();
            }

            $this->event($reconciliation, 'reconciliation_confirmed', $fromStatus, $data, $fingerprint, $actor);

            return ['data' => $this->present($reconciliation), 'replayed' => false, 'created' => $created];
        });
    }

    /** @param array<string, mixed> $data @return array{data: array<string, mixed>, replayed: bool, created: bool} */
    public function reopen(string $statementPublicId, string $paymentPublicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('compensation.manage'), 403);
        $fingerprint = $this->fingerprint('reopen', $statementPublicId, $paymentPublicId, $organizationId, $data);

        return DB::transaction(function () use ($statementPublicId, $paymentPublicId, $data, $organizationId, $actor, $fingerprint): array {
            $replay = $this->replay((string) $data['idempotency_key'], $fingerprint, $organizationId);
            if ($replay instanceof FinancialSettlementBankPaymentReconciliation) {
                return ['data' => $this->present($replay), 'replayed' => true, 'created' => false];
            }

            $payment = $this->payment($statementPublicId, $paymentPublicId, $organizationId);
            $reconciliation = FinancialSettlementBankPaymentReconciliation::query()
                ->where('owner_organization_id', $organizationId)
                ->where('financial_settlement_bank_payment_id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $reconciliation->revision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The reconciliation revision is stale.']]);
            }
            if ($reconciliation->status !== FinancialSettlementBankPaymentReconciliation::STATUS_CONFIRMED) {
                throw ValidationException::withMessages(['reconciliation' => ['Only a confirmed reconciliation may be reopened.']]);
            }

            $fromStatus = $reconciliation->status;
            $reconciliation->fill([
                'status' => FinancialSettlementBankPaymentReconciliation::STATUS_OPEN,
                'revision' => (int) $reconciliation->revision + 1, 'reason' => $data['reason'],
                'reopened_by_user_id' => $actor->id, 'reopened_at' => now(),
            ])->save();
            $this->event($reconciliation, 'reconciliation_reopened', $fromStatus, $data, $fingerprint, $actor);

            return ['data' => $this->present($reconciliation), 'replayed' => false, 'created' => false];
        });
    }

    private function payment(string $statementPublicId, string $paymentPublicId, int $organizationId): FinancialSettlementBankPayment
    {
        return FinancialSettlementBankPayment::query()
            ->where('public_id', $paymentPublicId)
            ->where('owner_organization_id', $organizationId)
            ->whereHas('statement', static fn (Builder $query): Builder => $query->where('public_id', $statementPublicId)->where('owner_organization_id', $organizationId))
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function replay(string $idempotencyKey, string $fingerprint, int $organizationId): ?FinancialSettlementBankPaymentReconciliation
    {
        $event = FinancialSettlementBankPaymentReconciliationEvent::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('owner_organization_id', $organizationId)
            ->first();
        if (! $event instanceof FinancialSettlementBankPaymentReconciliationEvent) {
            return null;
        }
        if (! hash_equals((string) $event->command_fingerprint, $fingerprint)) {
            throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
        }

        return FinancialSettlementBankPaymentReconciliation::query()->whereKey($event->financial_settlement_bank_payment_reconciliation_id)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    private function fingerprint(string $action, string $statementPublicId, string $paymentPublicId, int $organizationId, array $data): string
    {
        return hash('sha256', json_encode([
            'action' => $action, 'statement' => $statementPublicId, 'payment' => $paymentPublicId,
            'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data),
        ], JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $data */
    private function event(FinancialSettlementBankPaymentReconciliation $reconciliation, string $eventType, ?string $fromStatus, array $data, string $fingerprint, User $actor): void
    {
        FinancialSettlementBankPaymentReconciliationEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $reconciliation->owner_organization_id,
            'financial_settlement_bank_payment_reconciliation_id' => $reconciliation->id,
            'revision' => $reconciliation->revision, 'event_type' => $eventType,
            'from_status' => $fromStatus, 'to_status' => $reconciliation->status,
            'idempotency_key' => $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
            'reason' => $data['reason'], 'evidence' => [
                'payment_revision' => $reconciliation->payment_revision,
                'payment_modified' => false, 'settlement_statement_modified' => false,
                'billing_document_modified' => false, 'bank_transaction_evidence_modified' => false,
                'accounting_entry_created' => false,
            ], 'actor_user_id' => $actor->id, 'occurred_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function present(FinancialSettlementBankPaymentReconciliation $reconciliation): array
    {
        return [
            'public_id' => (string) $reconciliation->public_id,
            'payment_public_id' => (string) $reconciliation->payment()->value('public_id'),
            'status' => (string) $reconciliation->status, 'revision' => (int) $reconciliation->revision,
            'payment_revision' => (int) $reconciliation->payment_revision, 'reason' => (string) $reconciliation->reason,
            'payment_modified' => false, 'settlement_statement_modified' => false,
            'billing_document_modified' => false, 'bank_transaction_evidence_modified' => false,
            'accounting_entry_created' => false,
        ];
    }
}
