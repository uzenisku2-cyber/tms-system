<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Models\BankStatementImportDuplicateCandidate;
use App\Modules\Fleet\Models\BankStatementImportDuplicateResolution;
use App\Modules\Fleet\Models\BankStatementImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BankStatementImportDuplicateResolutionService
{
    public function resolve(BankStatementImportDuplicateCandidate $candidate, array $data, int $organizationId, User $actor): BankStatementImportDuplicateResolution
    {
        abort_unless($actor->can('compensation.manage'), 403);

        return DB::transaction(function () use ($candidate, $data, $organizationId, $actor): BankStatementImportDuplicateResolution {
            $locked = BankStatementImportDuplicateCandidate::query()->whereKey($candidate->getKey())->lockForUpdate()->firstOrFail();
            $locked->loadMissing('row.batch');
            $row = $locked->row;
            if (! $row instanceof BankStatementImportRow) {
                abort(404);
            }
            $batch = $row->batch;
            if (! $batch instanceof BankStatementImportBatch || (int) $batch->organization_context_id !== $organizationId) {
                abort(404);
            }
            $replay = BankStatementImportDuplicateResolution::query()->where('organization_context_id', $organizationId)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($replay instanceof BankStatementImportDuplicateResolution) {
                if ((int) $replay->bank_statement_import_duplicate_candidate_id !== (int) $locked->id || $replay->decision !== $data['decision'] || $replay->reason !== $data['reason']) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key belongs to another duplicate resolution.']]);
                }

                return $replay;
            }
            if (BankStatementImportDuplicateResolution::query()->where('bank_statement_import_duplicate_candidate_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['candidate' => ['This duplicate candidate is already resolved.']]);
            }

            return BankStatementImportDuplicateResolution::query()->create(['public_id' => (string) Str::uuid(), 'organization_context_id' => $organizationId, 'bank_statement_import_duplicate_candidate_id' => $locked->id, 'idempotency_key' => $data['idempotency_key'], 'decision' => $data['decision'], 'reason' => $data['reason'], 'resolved_by_user_id' => $actor->id, 'resolved_at' => now()]);
        });
    }
}
