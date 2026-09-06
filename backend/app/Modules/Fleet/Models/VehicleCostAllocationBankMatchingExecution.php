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

final class VehicleCostAllocationBankMatchingExecution extends Model
{
    protected $table = 'vehicle_cost_allocation_bank_matching_executions';

    protected $fillable = ['public_id', 'bank_matching_handoff_id', 'bank_transaction_evidence_id', 'billing_document_id', 'organization_context_id', 'idempotency_key', 'handoff_revision', 'bank_transaction_evidence_revision', 'matched_amount', 'currency', 'effective_date', 'allocation_source', 'status', 'reason', 'executed_by_user_id', 'executed_at', 'revision'];

    protected function casts(): array
    {
        return ['handoff_revision' => 'integer', 'bank_transaction_evidence_revision' => 'integer', 'matched_amount' => 'decimal:2', 'effective_date' => 'date', 'executed_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank matching executions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank matching executions are append-only.'));
    }

    public function handoff(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationBankMatchingHandoff::class, 'bank_matching_handoff_id');
    }

    public function transactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class, 'bank_transaction_evidence_id');
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationBankMatchingExecutionEvent::class, 'bank_matching_execution_id');
    }
}
