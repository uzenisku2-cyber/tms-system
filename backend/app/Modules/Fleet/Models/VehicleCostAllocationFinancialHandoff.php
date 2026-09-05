<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class VehicleCostAllocationFinancialHandoff extends Model
{
    protected $fillable = ['public_id', 'handoff_uid', 'vehicle_cost_allocation_id', 'allocation_uid', 'allocation_revision', 'organization_context_id', 'status', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'prepared_by_user_id', 'prepared_at', 'revision', 'financial_automation_performed'];

    protected function casts(): array
    {
        return ['allocation_revision' => 'integer', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'prepared_at' => 'immutable_datetime', 'revision' => 'integer', 'financial_automation_performed' => 'boolean'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial handoffs are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial handoffs are append-only.'));
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocation::class, 'vehicle_cost_allocation_id');
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationFinancialHandoffInstruction::class, 'financial_handoff_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationFinancialHandoffEvent::class, 'financial_handoff_id');
    }
}
