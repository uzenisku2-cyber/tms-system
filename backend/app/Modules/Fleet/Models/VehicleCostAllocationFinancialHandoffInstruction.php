<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class VehicleCostAllocationFinancialHandoffInstruction extends Model
{
    protected $fillable = ['public_id', 'financial_handoff_id', 'vehicle_cost_allocation_line_id', 'line_uid', 'sequence_number', 'settlement_mode', 'destination_type', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'external_party_name', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'vat_treatment', 'requires_invoice', 'bank_matching_eligible', 'execution_status', 'revision'];

    protected function casts(): array
    {
        return ['sequence_number' => 'integer', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'requires_invoice' => 'boolean', 'bank_matching_eligible' => 'boolean', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial handoff instructions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial handoff instructions are append-only.'));
    }

    public function handoff(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationFinancialHandoff::class, 'financial_handoff_id');
    }

    public function allocationLine(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationLine::class, 'vehicle_cost_allocation_line_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationFinancialHandoffExecution::class, 'financial_handoff_instruction_id');
    }
}
