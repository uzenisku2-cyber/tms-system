<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementBankPaymentReconciliationEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_bank_payment_reconciliation_id', 'revision',
        'event_type', 'from_status', 'to_status', 'idempotency_key', 'command_fingerprint',
        'reason', 'evidence', 'actor_user_id', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankPaymentReconciliation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement bank payment reconciliation events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement bank payment reconciliation events are append-only.'));
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
