<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class FinancialSettlementAccountingPostingReversal extends Model
{
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_accounting_posting_execution_id',
        'idempotency_key', 'command_fingerprint', 'currency', 'total_debit_minor', 'total_credit_minor',
        'status', 'reason', 'source_snapshot', 'revision', 'reversed_by_user_id', 'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'owner_organization_id' => 'integer',
            'financial_settlement_accounting_posting_execution_id' => 'integer',
            'total_debit_minor' => 'integer', 'total_credit_minor' => 'integer', 'source_snapshot' => 'array',
            'revision' => 'integer', 'reversed_by_user_id' => 'integer', 'reversed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new RuntimeException('Accounting posting reversals are append-only.');
        });
        self::deleting(static function (): never {
            throw new RuntimeException('Accounting posting reversals cannot be deleted.');
        });
    }

    /** @return BelongsTo<FinancialSettlementAccountingPostingExecution, $this> */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingExecution::class, 'financial_settlement_accounting_posting_execution_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }

    /** @return HasMany<FinancialSettlementAccountingPostingReversalEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingReversalEntry::class, 'financial_settlement_accounting_posting_reversal_id')->orderBy('sequence');
    }

    /** @return HasMany<FinancialSettlementAccountingPostingReversalEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingReversalEvent::class, 'financial_settlement_accounting_posting_reversal_id')->orderBy('revision');
    }
}
