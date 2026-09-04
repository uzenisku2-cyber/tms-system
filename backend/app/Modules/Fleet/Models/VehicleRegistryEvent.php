<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class VehicleRegistryEvent extends Model
{
    protected $fillable = ['public_id', 'vehicle_id', 'organization_context_id', 'actor_user_id', 'event_type', 'vehicle_revision', 'reason', 'payload', 'occurred_at'];

    protected $casts = ['vehicle_revision' => 'integer', 'payload' => 'array', 'occurred_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Vehicle registry events are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Vehicle registry events are append-only.'));
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
