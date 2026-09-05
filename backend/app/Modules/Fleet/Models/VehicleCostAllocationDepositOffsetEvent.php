<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class VehicleCostAllocationDepositOffsetEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['public_id', 'deposit_offset_acknowledgement_id', 'event_type', 'evidence', 'actor_user_id', 'revision', 'occurred_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'revision' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Deposit offset events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Deposit offset events are append-only.'));
    }

    public function acknowledgement(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationDepositOffsetAcknowledgement::class, 'deposit_offset_acknowledgement_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
