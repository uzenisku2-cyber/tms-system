<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class VehicleCostAllocationRepairFundReservation extends Model
{
    protected $fillable = ['public_id', 'financial_handoff_instruction_id', 'organization_context_id', 'idempotency_key', 'instruction_revision', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'reserve_purpose', 'evidence_note', 'status', 'reserved_by_user_id', 'reserved_at', 'revision'];

    protected function casts(): array
    {
        return ['instruction_revision' => 'integer', 'net_amount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'gross_amount' => 'decimal:2', 'reserved_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Repair fund reservations are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Repair fund reservations are append-only.'));
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(VehicleCostAllocationFinancialHandoffInstruction::class, 'financial_handoff_instruction_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VehicleCostAllocationRepairFundEvent::class, 'repair_fund_reservation_id');
    }
}
