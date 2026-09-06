<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class BankTransactionEvidenceEvent extends Model
{
    protected $table = 'bank_transaction_evidence_events';

    protected $fillable = ['public_id', 'bank_transaction_evidence_id', 'event_type', 'evidence', 'actor_user_id', 'revision', 'occurred_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'revision' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank transaction evidence events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank transaction evidence events are append-only.'));
    }

    public function transactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class, 'bank_transaction_evidence_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
