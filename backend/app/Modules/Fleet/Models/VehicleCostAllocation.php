<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

/** @property int $id */
class VehicleCostAllocation extends Model
{
    protected $fillable = ['public_id', 'allocation_uid', 'vehicle_id', 'organization_context_id', 'source_type', 'source_reference_uid', 'source_document_reference', 'occurred_on', 'description', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'status', 'recorded_by_user_id', 'approved_by_user_id', 'approved_at', 'revision', 'notes'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'approved_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new RuntimeException('Vehicle cost allocations are append-only.'));
        static::deleting(static fn (): never => throw new RuntimeException('Vehicle cost allocations are append-only.'));
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationLine::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationEvent::class);
    }

    public function financialHandoffs(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationFinancialHandoff::class);
    }
}
