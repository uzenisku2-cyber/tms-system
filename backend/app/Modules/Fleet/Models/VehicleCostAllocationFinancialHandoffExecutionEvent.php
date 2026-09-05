<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

final class VehicleCostAllocationFinancialHandoffExecutionEvent extends Model
{
    protected $table = 'vehicle_cost_allocation_handoff_execution_events';

    public $timestamps = false;

    protected $fillable = ['public_id', 'handoff_execution_id', 'event_type', 'evidence', 'actor_user_id', 'revision', 'occurred_at'];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'revision' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial handoff execution events are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial handoff execution events are append-only.'));
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationFinancialHandoffExecution::class, 'handoff_execution_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
