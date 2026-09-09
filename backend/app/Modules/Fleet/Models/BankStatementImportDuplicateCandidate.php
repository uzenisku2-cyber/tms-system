<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

final class BankStatementImportDuplicateCandidate extends Model
{
    protected $fillable = ['public_id', 'bank_statement_import_row_id', 'candidate_bank_transaction_evidence_id', 'comparison_method', 'matching_fields', 'confidence', 'decision', 'detected_at'];

    protected function casts(): array
    {
        return ['matching_fields' => 'array', 'confidence' => 'decimal:4', 'detected_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank statement duplicate candidates are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank statement duplicate candidates are append-only.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(BankStatementImportDuplicateResolution::class, 'bank_statement_import_duplicate_candidate_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(BankStatementImportRow::class, 'bank_statement_import_row_id');
    }

    public function candidateEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class, 'candidate_bank_transaction_evidence_id');
    }
}
