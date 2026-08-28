<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelImportRowCorrection;
use App\Modules\Fuel\Models\FuelImportRowFinalization;
use App\Modules\Fuel\Models\FuelTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class FuelImportFinalizationService
{
    /** @return array{public_id:string,source_row:int,status:string,transaction_public_id:string,correction_revision:int} */
    public function finalize(FuelImportBatch $batch, int $sourceRow, int $organizationId, User $actor, int $expectedRevision, string $reason): array
    {
        abort_unless((int) $batch->owner_organization_id === $organizationId, 404);

        return DB::transaction(function () use ($batch, $sourceRow, $organizationId, $actor, $expectedRevision, $reason): array {
            $lockedBatch = FuelImportBatch::query()->whereKey($batch->getKey())->lockForUpdate()->firstOrFail();
            $row = FuelImportRow::query()->where('fuel_import_batch_id', $lockedBatch->getKey())->where('source_row', $sourceRow)->lockForUpdate()->firstOrFail();

            if (! in_array($row->status, ['review', 'rejected'], true)) {
                throw ValidationException::withMessages(['source_row' => ['Only review or rejected rows can be finalized.']]);
            }
            if (FuelImportRowFinalization::query()->where('fuel_import_row_id', $row->getKey())->exists()) {
                throw ValidationException::withMessages(['source_row' => ['This row has already been finalized.']]);
            }

            $corrections = FuelImportRowCorrection::query()->where('fuel_import_row_id', $row->getKey())->orderByDesc('revision')->lockForUpdate()->limit(1)->get();
            if ($corrections->isEmpty()) {
                throw ValidationException::withMessages(['expected_correction_revision' => ['At least one audited correction is required before finalization.']]);
            }
            $correction = $corrections->first();
            if ($correction->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_correction_revision' => ['The reviewed correction revision is stale. Reload the row before finalization.']]);
            }

            $payload = $this->canonicalPayload($correction->corrected_payload);
            $messages = $this->validatePayload($payload);
            if ($messages !== []) {
                throw ValidationException::withMessages(['corrected_payload' => $messages]);
            }

            $match = $this->match($organizationId, (string) $lockedBatch->provider, (string) $payload['provider_card_identifier'], (string) $payload['occurred_at']);
            if ($match['match_status'] !== 'matched') {
                throw ValidationException::withMessages(['corrected_payload.provider_card_identifier' => ['The corrected row still has no single valid fuel-card assignment.']]);
            }

            $fingerprint = $this->fingerprint($payload);
            $existingTransactionId = $row->fuel_transaction_id === null ? null : (int) $row->fuel_transaction_id;
            $duplicate = FuelTransaction::query()->where('owner_organization_id', $organizationId)->where('provider', $lockedBatch->provider)->where('transaction_fingerprint', $fingerprint)->when($existingTransactionId !== null, static fn ($query) => $query->whereKeyNot($existingTransactionId))->lockForUpdate()->first();
            if ($duplicate instanceof FuelTransaction) {
                throw ValidationException::withMessages(['corrected_payload' => ['The corrected row duplicates an existing fuel transaction.']]);
            }

            $transaction = $existingTransactionId === null ? null : FuelTransaction::query()->whereKey($existingTransactionId)->lockForUpdate()->firstOrFail();
            $before = $transaction?->attributesToArray();
            $attributes = [...$payload, ...$match, 'owner_organization_id' => $organizationId, 'provider' => $lockedBatch->provider, 'transaction_fingerprint' => $fingerprint, 'fuel_import_batch_id' => $lockedBatch->getKey(), 'source_row' => $row->source_row];

            if ($transaction instanceof FuelTransaction) {
                $transaction->forceFill($attributes)->save();
            } else {
                $transaction = FuelTransaction::query()->create(['public_id' => (string) Str::uuid(), ...$attributes]);
            }

            $fromStatus = (string) $row->status;
            $row->forceFill(['status' => 'accepted', 'fuel_transaction_id' => $transaction->getKey()])->save();

            $finalization = FuelImportRowFinalization::query()->create([
                'public_id' => (string) Str::uuid(),
                'fuel_import_row_id' => $row->getKey(),
                'fuel_import_row_correction_id' => $correction->getKey(),
                'fuel_transaction_id' => $transaction->getKey(),
                'correction_revision' => $correction->revision,
                'from_status' => $fromStatus,
                'to_status' => 'accepted',
                'before_snapshot' => $before,
                'after_snapshot' => $transaction->fresh()->attributesToArray(),
                'reason' => trim($reason),
                'finalized_by_user_id' => $actor->getKey(),
                'finalized_at' => now(),
            ]);

            $this->refreshBatch($lockedBatch);

            return ['public_id' => $finalization->public_id, 'source_row' => (int) $row->source_row, 'status' => 'accepted', 'transaction_public_id' => $transaction->public_id, 'correction_revision' => $correction->revision];
        }, 3);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function canonicalPayload(array $payload): array
    {
        $keys = ['provider_transaction_identifier', 'occurred_at', 'posting_date', 'provider_card_identifier', 'station_identifier', 'station_name', 'station_address', 'product_code', 'product_name', 'quantity', 'unit_of_measure', 'unit_price', 'net_amount', 'tax_amount', 'gross_amount', 'discount_amount', 'tax_rate', 'currency', 'vehicle_registration', 'odometer', 'invoice_reference', 'source_description'];

        return array_intersect_key($payload, array_flip($keys)) + array_fill_keys($keys, null);
    }

    /** @param array<string,mixed> $payload @return list<string> */
    private function validatePayload(array $payload): array
    {
        $messages = [];
        foreach (['occurred_at', 'provider_card_identifier', 'product_name', 'quantity', 'gross_amount', 'currency'] as $field) {
            if ($payload[$field] === null || $payload[$field] === '') {
                $messages[] = "Missing required normalized value {$field}.";
            }
        }
        if ($payload['unit_of_measure'] !== 'L') {
            $messages[] = 'The supported liquid-fuel unit is L.';
        }
        if ($payload['quantity'] !== null && (! is_numeric($payload['quantity']) || (float) $payload['quantity'] <= 0)) {
            $messages[] = 'Quantity must be greater than zero.';
        }
        if ($payload['gross_amount'] !== null && (! is_numeric($payload['gross_amount']) || (float) $payload['gross_amount'] < 0)) {
            $messages[] = 'Gross amount must not be negative.';
        }
        if (! is_string($payload['currency']) || preg_match('/^[A-Z]{3}$/', $payload['currency']) !== 1) {
            $messages[] = 'Currency must contain exactly three uppercase letters.';
        }
        if (! is_string($payload['occurred_at']) || strtotime($payload['occurred_at']) === false) {
            $messages[] = 'Occurrence timestamp is invalid.';
        }

        return $messages;
    }

    /** @param array<string,mixed> $payload */
    private function fingerprint(array $payload): string
    {
        return strtoupper(hash('sha256', json_encode(array_intersect_key($payload, array_flip(['provider_transaction_identifier', 'occurred_at', 'provider_card_identifier', 'station_identifier', 'product_code', 'quantity', 'gross_amount', 'currency'])), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));
    }

    /** @return array<string,mixed> */
    private function match(int $organizationId, string $provider, string $cardIdentifier, string $occurredAt): array
    {
        $card = FuelCard::query()->where('owner_organization_id', $organizationId)->where('provider', $provider)->where('provider_card_identifier', $cardIdentifier)->first();
        if (! $card instanceof FuelCard) {
            return ['fuel_card_id' => null, 'fuel_card_assignment_id' => null, 'responsible_organization_id' => null, 'driver_id' => null, 'vehicle_id' => null, 'match_status' => 'review', 'match_method' => 'unknown_card'];
        }
        $assignments = FuelCardAssignment::query()->where('fuel_card_id', $card->getKey())->where('valid_from', '<=', $occurredAt)->where(static function ($query) use ($occurredAt): void {
            $query->whereNull('valid_until')->orWhere('valid_until', '>=', $occurredAt);
        })->get();
        if ($assignments->count() !== 1) {
            return ['fuel_card_id' => $card->getKey(), 'fuel_card_assignment_id' => null, 'responsible_organization_id' => null, 'driver_id' => null, 'vehicle_id' => null, 'match_status' => 'review', 'match_method' => $assignments->isEmpty() ? 'no_valid_assignment' : 'conflicting_assignments'];
        }
        $assignment = $assignments->first();
        if (! $assignment instanceof FuelCardAssignment) {
            throw new RuntimeException('The effective fuel-card assignment could not be resolved.');
        }

        return ['fuel_card_id' => $card->getKey(), 'fuel_card_assignment_id' => $assignment->getKey(), 'responsible_organization_id' => $assignment->responsible_organization_id, 'driver_id' => $assignment->driver_id, 'vehicle_id' => $assignment->vehicle_id, 'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period'];
    }

    private function refreshBatch(FuelImportBatch $batch): void
    {
        $counts = FuelImportRow::query()->where('fuel_import_batch_id', $batch->getKey())->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $review = (int) ($counts['review'] ?? 0);
        $rejected = (int) ($counts['rejected'] ?? 0);
        $batch->forceFill([
            'status' => $review + $rejected === 0 ? 'completed' : 'completed_with_review',
            'accepted_row_count' => (int) ($counts['accepted'] ?? 0),
            'duplicate_row_count' => (int) ($counts['duplicate'] ?? 0),
            'review_row_count' => $review,
            'rejected_row_count' => $rejected,
        ])->save();
    }
}
