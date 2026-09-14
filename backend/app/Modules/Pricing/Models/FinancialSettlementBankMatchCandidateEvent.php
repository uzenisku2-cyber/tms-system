<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementBankMatchCandidateEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'financial_settlement_bank_match_candidate_id', 'revision',
        'event_type', 'idempotency_key', 'candidate_fingerprint', 'reason',
        'evidence', 'actor_user_id', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankMatchCandidate::class, 'financial_settlement_bank_match_candidate_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement bank match candidate events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement bank match candidate events are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'evidence' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
