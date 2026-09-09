<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionAmountBreakdown;
use App\Modules\Fleet\Models\BankTransactionAmountBreakdownComponent;
use App\Modules\Fleet\Models\BankTransactionAmountBreakdownEvent;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BankTransactionAmountBreakdownService
{
    public function show(BankTransactionEvidence $evidence, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $this->assertOwned($evidence, $organizationId);

        $revisions = BankTransactionAmountBreakdown::query()
            ->where('organization_context_id', $organizationId)
            ->where('bank_transaction_evidence_id', $evidence->getKey())
            ->with(['components', 'events'])
            ->orderBy('revision')
            ->get();

        return [
            'evidence_public_id' => $evidence->public_id,
            'source_amount' => $evidence->amount,
            'source_amount_minor' => $this->minorUnits((string) $evidence->amount),
            'currency' => $evidence->currency,
            'latest_revision' => $revisions->isEmpty() ? 0 : (int) $revisions->last()->revision,
            'revisions' => $revisions->map(fn (BankTransactionAmountBreakdown $revision): array => $this->present($revision))->all(),
            'automatic_matching_performed' => false,
            'payment_marked' => false,
            'billing_document_mutated' => false,
        ];
    }

    public function store(BankTransactionEvidence $evidence, array $data, int $organizationId, User $actor): array
    {
        $this->authorize($actor);
        $this->assertOwned($evidence, $organizationId);

        return DB::transaction(function () use ($evidence, $data, $organizationId, $actor): array {
            $lockedEvidence = BankTransactionEvidence::query()
                ->whereKey($evidence->getKey())
                ->where('organization_context_id', $organizationId)
                ->lockForUpdate()
                ->firstOrFail();

            $replay = BankTransactionAmountBreakdown::query()
                ->where('organization_context_id', $organizationId)
                ->where('idempotency_key', $data['idempotency_key'])
                ->with(['components', 'events'])
                ->first();

            if ($replay instanceof BankTransactionAmountBreakdown) {
                if ((int) $replay->bank_transaction_evidence_id !== (int) $lockedEvidence->getKey()) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another bank transaction amount breakdown.']]);
                }
                if (! $this->isSameCommand($replay, $data)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used for a different amount breakdown command.']]);
                }

                return $this->present($replay);
            }

            $latest = BankTransactionAmountBreakdown::query()
                ->where('organization_context_id', $organizationId)
                ->where('bank_transaction_evidence_id', $lockedEvidence->getKey())
                ->orderByDesc('revision')
                ->lockForUpdate()
                ->first();

            $currentRevision = $latest instanceof BankTransactionAmountBreakdown ? (int) $latest->revision : 0;
            if ($currentRevision !== (int) $data['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The bank transaction amount breakdown revision is stale.']]);
            }
            if ($latest instanceof BankTransactionAmountBreakdown && $latest->status === 'finalized') {
                throw ValidationException::withMessages(['breakdown' => ['A finalized bank transaction amount breakdown cannot be changed.']]);
            }

            $sourceAmountMinor = $this->minorUnits((string) $lockedEvidence->amount);
            $components = [];
            $allocatedAmountMinor = 0;

            foreach (array_values($data['components']) as $index => $component) {
                $label = isset($component['label']) ? trim((string) $component['label']) : null;
                if ($component['type'] === 'custom' && ($label === null || $label === '')) {
                    throw ValidationException::withMessages(["components.$index.label" => ['A custom component label is required.']]);
                }

                $amountMinor = $this->minorUnits((string) $component['amount']);
                $allocatedAmountMinor += $amountMinor;
                $components[] = [
                    'sequence_number' => $index + 1,
                    'component_type' => $component['type'],
                    'label' => $label,
                    'amount_minor' => $amountMinor,
                    'metadata' => $component['metadata'] ?? null,
                ];
            }

            if ($allocatedAmountMinor !== $sourceAmountMinor) {
                throw ValidationException::withMessages(['components' => ['The component sum must equal the bank transaction amount exactly to one cent.']]);
            }

            $nextRevision = $currentRevision + 1;
            $breakdownUid = $latest instanceof BankTransactionAmountBreakdown ? $latest->breakdown_uid : (string) Str::uuid();
            $recordedAt = now();
            $status = $data['finalize'] ? 'finalized' : 'draft';

            $breakdown = BankTransactionAmountBreakdown::query()->create([
                'public_id' => (string) Str::uuid(),
                'breakdown_uid' => $breakdownUid,
                'organization_context_id' => $organizationId,
                'bank_transaction_evidence_id' => $lockedEvidence->getKey(),
                'idempotency_key' => $data['idempotency_key'],
                'revision' => $nextRevision,
                'status' => $status,
                'source_amount_minor' => $sourceAmountMinor,
                'allocated_amount_minor' => $allocatedAmountMinor,
                'currency' => $lockedEvidence->currency,
                'reason' => trim((string) $data['reason']),
                'created_by_user_id' => $actor->getAuthIdentifier(),
                'recorded_at' => $recordedAt,
            ]);

            foreach ($components as $component) {
                BankTransactionAmountBreakdownComponent::query()->create($component + [
                    'public_id' => (string) Str::uuid(),
                    'bank_transaction_amount_breakdown_id' => $breakdown->getKey(),
                ]);
            }

            BankTransactionAmountBreakdownEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'bank_transaction_amount_breakdown_id' => $breakdown->getKey(),
                'event_type' => $status === 'finalized' ? 'breakdown_finalized' : 'breakdown_revision_recorded',
                'evidence' => [
                    'breakdown_uid' => $breakdownUid,
                    'revision' => $nextRevision,
                    'source_amount_minor' => $sourceAmountMinor,
                    'allocated_amount_minor' => $allocatedAmountMinor,
                    'component_count' => count($components),
                ],
                'actor_user_id' => $actor->getAuthIdentifier(),
                'occurred_at' => $recordedAt,
            ]);

            return $this->present($breakdown->load(['components', 'events']));
        });
    }

    private function isSameCommand(BankTransactionAmountBreakdown $replay, array $data): bool
    {
        if ((int) $replay->revision !== ((int) $data['expected_revision'] + 1)
            || (string) $replay->status !== ($data['finalize'] ? 'finalized' : 'draft')
            || (string) $replay->reason !== trim((string) $data['reason'])) {
            return false;
        }

        $storedComponents = $replay->components->values();
        $requestedComponents = array_values($data['components']);
        if ($storedComponents->count() !== count($requestedComponents)) {
            return false;
        }

        foreach ($requestedComponents as $index => $requested) {
            $stored = $storedComponents->get($index);
            $requestedLabel = isset($requested['label']) ? trim((string) $requested['label']) : null;
            if (! $stored instanceof BankTransactionAmountBreakdownComponent
                || (int) $stored->sequence_number !== $index + 1
                || (string) $stored->component_type !== (string) $requested['type']
                || $stored->label !== $requestedLabel
                || (int) $stored->amount_minor !== $this->minorUnits((string) $requested['amount'])
                || $this->canonicalValue($stored->metadata) !== $this->canonicalValue($requested['metadata'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function canonicalValue(mixed $value): string
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value);
            }
            foreach ($value as $key => $item) {
                $value[$key] = is_array($item) ? json_decode($this->canonicalValue($item), true, 512, JSON_THROW_ON_ERROR) : $item;
            }
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function minorUnits(string $amount): int
    {
        $normalized = str_replace(',', '.', trim($amount));
        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '+-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        $minor = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$minor : $minor;
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->can('compensation.manage'), 403);
    }

    private function assertOwned(BankTransactionEvidence $evidence, int $organizationId): void
    {
        abort_unless((int) $evidence->organization_context_id === $organizationId, 404);
    }

    private function present(BankTransactionAmountBreakdown $breakdown): array
    {
        return [
            'public_id' => $breakdown->public_id,
            'breakdown_uid' => $breakdown->breakdown_uid,
            'revision' => (int) $breakdown->revision,
            'status' => $breakdown->status,
            'source_amount_minor' => (int) $breakdown->source_amount_minor,
            'allocated_amount_minor' => (int) $breakdown->allocated_amount_minor,
            'currency' => $breakdown->currency,
            'reason' => $breakdown->reason,
            'components' => BankTransactionAmountBreakdownComponent::query()
                ->where('bank_transaction_amount_breakdown_id', $breakdown->getKey())
                ->orderBy('sequence_number')
                ->get()
                ->map(fn (BankTransactionAmountBreakdownComponent $component): array => [
                    'public_id' => $component->public_id,
                    'sequence_number' => (int) $component->sequence_number,
                    'type' => $component->component_type,
                    'label' => $component->label,
                    'amount_minor' => (int) $component->amount_minor,
                    'metadata' => $component->metadata === null ? null : (array) $component->metadata,
                ])->all(),
            'events' => BankTransactionAmountBreakdownEvent::query()
                ->where('bank_transaction_amount_breakdown_id', $breakdown->getKey())
                ->orderBy('id')
                ->get()
                ->map(fn (BankTransactionAmountBreakdownEvent $event): array => [
                    'event_type' => $event->event_type,
                    'evidence' => (array) $event->evidence,
                    'occurred_at' => Carbon::parse((string) $event->occurred_at)->toAtomString(),
                ])->all(),
            'automatic_matching_performed' => false,
            'payment_marked' => false,
            'billing_document_mutated' => false,
        ];
    }
}
