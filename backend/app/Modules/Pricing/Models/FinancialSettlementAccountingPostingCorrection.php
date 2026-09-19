<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class FinancialSettlementAccountingPostingCorrection extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'original_execution_id', 'reversal_id',
        'replacement_execution_id', 'idempotency_key', 'command_fingerprint',
        'reason', 'source_snapshot', 'corrected_by_user_id', 'corrected_at', 'revision',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement accounting posting corrections are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting posting corrections are append-only.'));
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function originalExecution(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingExecution::class, 'original_execution_id');
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingReversal::class, 'reversal_id');
    }

    public function replacementExecution(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingExecution::class, 'replacement_execution_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingCorrectionEvent::class, 'correction_id')->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['source_snapshot' => 'array', 'corrected_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
