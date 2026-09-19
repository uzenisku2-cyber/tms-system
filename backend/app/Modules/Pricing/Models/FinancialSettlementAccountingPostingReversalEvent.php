<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementAccountingPostingReversalEvent extends Model
{
    public const TYPE_REVERSED = 'reversed';

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_accounting_posting_reversal_id',
        'event_type', 'revision', 'payload', 'actor_user_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'owner_organization_id' => 'integer', 'financial_settlement_accounting_posting_reversal_id' => 'integer',
            'revision' => 'integer', 'payload' => 'array', 'actor_user_id' => 'integer', 'occurred_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new RuntimeException('Accounting posting reversal events are append-only.');
        });
        self::deleting(static function (): never {
            throw new RuntimeException('Accounting posting reversal events cannot be deleted.');
        });
    }

    /** @return BelongsTo<FinancialSettlementAccountingPostingReversal, $this> */
    public function reversal(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingReversal::class, 'financial_settlement_accounting_posting_reversal_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
