<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementStatementEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'financial_settlement_statement_id', 'revision',
        'event_type', 'idempotency_key', 'command_fingerprint',
        'from_status', 'to_status', 'reason', 'evidence', 'actor_user_id', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementStatement::class, 'financial_settlement_statement_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement statement events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement statement events are append-only.'));
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
