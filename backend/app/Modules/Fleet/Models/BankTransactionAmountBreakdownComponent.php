<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class BankTransactionAmountBreakdownComponent extends Model
{
    protected $fillable = ['public_id', 'bank_transaction_amount_breakdown_id', 'sequence_number', 'component_type', 'label', 'amount_minor', 'metadata'];

    protected function casts(): array
    {
        return ['sequence_number' => 'integer', 'amount_minor' => 'integer', 'metadata' => 'array'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown components are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown components are append-only.'));
    }

    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(BankTransactionAmountBreakdown::class, 'bank_transaction_amount_breakdown_id');
    }
}
