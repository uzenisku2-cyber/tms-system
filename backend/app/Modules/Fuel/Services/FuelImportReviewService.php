<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelImportRowCorrection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelImportReviewService
{
    public function row(FuelImportBatch $batch, int $sourceRow, int $organizationId): array
    {
        $this->assertVisibleBatch($batch, $organizationId);
        $row = $this->findRow($batch, $sourceRow);
        $corrections = FuelImportRowCorrection::query()
            ->where('fuel_import_row_id', $row->id)
            ->with('correctedBy:id,name')
            ->orderBy('revision')
            ->get();
        $latest = $corrections->last();

        return [
            'source_row' => $row->source_row,
            'status' => $row->status,
            'raw_payload' => $row->raw_payload,
            'normalized_payload' => $row->normalized_payload,
            'validation_messages' => $row->validation_messages,
            'effective_payload' => $corrections->isEmpty() ? $row->normalized_payload : $corrections->last()->corrected_payload,
            'corrections' => $corrections->map(fn (FuelImportRowCorrection $item): array => [
                'public_id' => $item->public_id,
                'revision' => $item->revision,
                'original_payload' => $item->original_payload,
                'corrected_payload' => $item->corrected_payload,
                'reason' => $item->reason,
                'corrected_by' => $item->correctedBy?->only(['id', 'name']),
                'created_at' => $item->created_at?->toISOString(),
            ])->values(),
        ];
    }

    public function correct(FuelImportBatch $batch, int $sourceRow, int $organizationId, User $actor, array $payload, string $reason): array
    {
        $this->assertVisibleBatch($batch, $organizationId);

        return DB::transaction(function () use ($batch, $sourceRow, $actor, $payload, $reason): array {
            $row = FuelImportRow::query()->where('fuel_import_batch_id', $batch->id)->where('source_row', $sourceRow)->lockForUpdate()->firstOrFail();
            if (! in_array($row->status, ['review', 'rejected'], true)) {
                throw ValidationException::withMessages(['source_row' => ['Only review or rejected rows can be corrected.']]);
            }

            $previousCorrections = FuelImportRowCorrection::query()
                ->where('fuel_import_row_id', $row->id)
                ->orderByDesc('revision')
                ->limit(1)
                ->get();

            if ($previousCorrections->isEmpty()) {
                $original = $row->normalized_payload ?? $row->raw_payload;
                $nextRevision = 1;
            } else {
                $latest = $previousCorrections->first();
                $original = $latest->corrected_payload;
                $nextRevision = $latest->revision + 1;
            }
            if ($payload === $original) {
                throw ValidationException::withMessages([
                    'corrected_payload' => ['The correction must change at least one value.'],
                ]);
            }
            $correction = FuelImportRowCorrection::query()->create([
                'fuel_import_row_id' => $row->id,
                'public_id' => (string) Str::uuid(),
                'revision' => $nextRevision,
                'original_payload' => $original,
                'corrected_payload' => $payload,
                'reason' => trim($reason),
                'corrected_by_user_id' => $actor->getKey(),
            ]);

            return ['public_id' => $correction->public_id, 'revision' => $correction->revision];
        });
    }

    private function assertVisibleBatch(FuelImportBatch $batch, int $organizationId): void
    {
        abort_unless((int) $batch->owner_organization_id === $organizationId, 404);
    }

    private function findRow(FuelImportBatch $batch, int $sourceRow): FuelImportRow
    {
        return FuelImportRow::query()->where('fuel_import_batch_id', $batch->id)->where('source_row', $sourceRow)->firstOrFail();
    }
}
