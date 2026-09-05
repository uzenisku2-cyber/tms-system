<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class VehicleCostAllocationFinancialHandoffExecution extends Model
{
    protected $table = 'vehicle_cost_allocation_handoff_executions';

    protected $fillable = ['public_id', 'financial_handoff_instruction_id', 'billing_document_id', 'organization_context_id', 'idempotency_key', 'instruction_revision', 'status', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'vat_rate_basis_points', 'executed_by_user_id', 'executed_at', 'revision'];

    protected function casts(): array
    {
        return ['instruction_revision' => 'integer', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'vat_rate_basis_points' => 'integer', 'executed_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial handoff executions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial handoff executions are append-only.'));
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationFinancialHandoffInstruction::class, 'financial_handoff_instruction_id');
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationFinancialHandoffExecutionEvent::class, 'handoff_execution_id');
    }
}
