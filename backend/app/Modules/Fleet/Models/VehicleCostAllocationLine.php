<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/** @property int $id */
class VehicleCostAllocationLine extends Model
{
    protected $fillable = ['public_id', 'line_uid', 'vehicle_cost_allocation_id', 'sequence_number', 'cost_component', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'external_party_name', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'settlement_mode', 'vat_treatment', 'vat_rate_basis_points', 'recorded_by_user_id', 'revision', 'notes'];

    protected function casts(): array
    {
        return ['sequence_number' => 'integer', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'vat_rate_basis_points' => 'integer', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new RuntimeException('Vehicle cost allocation lines are append-only.'));
        static::deleting(static fn (): never => throw new RuntimeException('Vehicle cost allocation lines are append-only.'));
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocation::class, 'vehicle_cost_allocation_id');
    }

    public function responsibleOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'responsible_organization_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
