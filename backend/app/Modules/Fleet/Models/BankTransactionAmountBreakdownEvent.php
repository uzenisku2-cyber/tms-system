<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class BankTransactionAmountBreakdownEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['public_id', 'bank_transaction_amount_breakdown_id', 'event_type', 'evidence', 'actor_user_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown events are append-only.'));
    }

    public function breakdown(): BelongsTo
    {
        return $this->belongsTo(BankTransactionAmountBreakdown::class, 'bank_transaction_amount_breakdown_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
