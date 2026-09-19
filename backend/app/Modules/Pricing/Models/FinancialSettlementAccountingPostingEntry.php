<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementAccountingPostingEntry extends Model
{
    use HasUuids;

    public const SIDE_DEBIT = 'debit';

    public const SIDE_CREDIT = 'credit';

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_accounting_posting_execution_id',
        'sequence_number', 'side', 'account_code', 'amount_minor', 'currency', 'description', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement accounting posting entries are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting posting entries are append-only.'));
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingExecution::class, 'financial_settlement_accounting_posting_execution_id');
    }

    protected function casts(): array
    {
        return ['sequence_number' => 'integer', 'amount_minor' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }
}
