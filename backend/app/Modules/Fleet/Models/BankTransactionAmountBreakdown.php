<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class BankTransactionAmountBreakdown extends Model
{
    protected $fillable = ['public_id', 'breakdown_uid', 'organization_context_id', 'bank_transaction_evidence_id', 'idempotency_key', 'revision', 'status', 'source_amount_minor', 'allocated_amount_minor', 'currency', 'reason', 'created_by_user_id', 'recorded_at'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'source_amount_minor' => 'integer', 'allocated_amount_minor' => 'integer', 'recorded_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown revisions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank transaction amount breakdown revisions are append-only.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function bankTransactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class, 'bank_transaction_evidence_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(BankTransactionAmountBreakdownComponent::class)->orderBy('sequence_number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BankTransactionAmountBreakdownEvent::class)->orderBy('id');
    }
}
