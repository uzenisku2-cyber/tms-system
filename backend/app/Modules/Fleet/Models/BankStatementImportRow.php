<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class BankStatementImportRow extends Model
{
    protected $fillable = ['bank_statement_import_batch_id', 'source_row', 'status', 'row_fingerprint', 'transaction_fingerprint', 'raw_payload', 'normalized_payload', 'validation_messages', 'bank_transaction_evidence_id'];

    protected $casts = ['source_row' => 'integer', 'raw_payload' => 'array', 'normalized_payload' => 'array', 'validation_messages' => 'array'];

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank statement import rows are immutable.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank statement import rows are immutable.'));
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BankStatementImportBatch::class, 'bank_statement_import_batch_id');
    }

    public function transactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class, 'bank_transaction_evidence_id');
    }

    public function duplicateCandidates(): HasMany
    {
        return $this->hasMany(BankStatementImportDuplicateCandidate::class, 'bank_statement_import_row_id');
    }
}
