<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialSettlementAccountingPeriodEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = ['public_id', 'accounting_period_id', 'event_type', 'idempotency_key', 'command_fingerprint', 'payload', 'actor_user_id', 'occurred_at', 'revision'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Accounting period events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Accounting period events are append-only.'));
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPeriod::class, 'accounting_period_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
