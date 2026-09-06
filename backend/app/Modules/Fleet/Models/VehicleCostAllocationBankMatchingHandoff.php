<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class VehicleCostAllocationBankMatchingHandoff extends Model
{
    protected $table = 'vehicle_cost_allocation_bank_matching_handoffs';

    protected $fillable = ['public_id', 'financial_handoff_instruction_id', 'billing_document_id', 'organization_context_id', 'idempotency_key', 'instruction_revision', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'bank_transaction_reference', 'bank_statement_reference', 'booked_at', 'evidence_amount', 'currency', 'counterparty_name', 'evidence_note', 'status', 'prepared_by_user_id', 'prepared_at', 'revision'];

    protected function casts(): array
    {
        return ['instruction_revision' => 'integer', 'booked_at' => 'date', 'evidence_amount' => 'decimal:2', 'prepared_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank matching handoffs are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank matching handoffs are append-only.'));
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationFinancialHandoffInstruction::class, 'financial_handoff_instruction_id');
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationBankMatchingHandoffEvent::class, 'bank_matching_handoff_id');
    }
}
