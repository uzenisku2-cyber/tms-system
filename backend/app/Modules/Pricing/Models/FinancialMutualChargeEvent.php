<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class FinancialMutualChargeEvent extends Model
{
    use HasUuids;

    public const TYPE_CREATED = 'created';

    public const TYPE_CONFIRMED = 'confirmed';

    public const TYPE_DISPUTED = 'disputed';

    public const TYPE_REVERSED = 'reversed';

    public $timestamps = false;

    protected $fillable = [
        'public_id',
        'financial_mutual_charge_id',
        'revision',
        'event_type',
        'idempotency_key',
        'command_fingerprint',
        'from_status',
        'to_status',
        'reason',
        'evidence',
        'actor_user_id',
        'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(FinancialMutualCharge::class, 'financial_mutual_charge_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial mutual charge events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial mutual charge events are append-only.'));
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
