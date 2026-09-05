<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/** @property int $id */
class VehicleCostAllocationEvent extends Model
{
    protected $fillable = ['public_id', 'vehicle_cost_allocation_id', 'event_type', 'from_status', 'to_status', 'evidence', 'actor_user_id', 'revision', 'occurred_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['evidence' => 'array', 'revision' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new RuntimeException('Vehicle cost allocation events are append-only.'));
        static::deleting(static fn (): never => throw new RuntimeException('Vehicle cost allocation events are append-only.'));
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocation::class, 'vehicle_cost_allocation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
