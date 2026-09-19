<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementAccountingPostingReversalEntry extends Model
{
    public const SIDE_DEBIT = 'debit';

    public const SIDE_CREDIT = 'credit';

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_accounting_posting_reversal_id',
        'original_posting_entry_id', 'sequence', 'entry_side', 'account_code', 'amount_minor',
        'currency', 'source_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'owner_organization_id' => 'integer', 'financial_settlement_accounting_posting_reversal_id' => 'integer',
            'original_posting_entry_id' => 'integer', 'sequence' => 'integer', 'amount_minor' => 'integer',
            'source_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new RuntimeException('Accounting posting reversal entries are append-only.');
        });
        self::deleting(static function (): never {
            throw new RuntimeException('Accounting posting reversal entries cannot be deleted.');
        });
    }

    /** @return BelongsTo<FinancialSettlementAccountingPostingReversal, $this> */
    public function reversal(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingReversal::class, 'financial_settlement_accounting_posting_reversal_id');
    }

    /** @return BelongsTo<FinancialSettlementAccountingPostingEntry, $this> */
    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingEntry::class, 'original_posting_entry_id');
    }
}
