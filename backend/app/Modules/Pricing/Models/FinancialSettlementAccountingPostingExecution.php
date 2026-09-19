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

final class FinancialSettlementAccountingPostingExecution extends Model
{
    use HasUuids;

    public const STATUS_POSTED = 'posted';

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_accounting_posting_handoff_id',
        'idempotency_key', 'command_fingerprint', 'handoff_revision', 'posting_date',
        'accounting_reference', 'amount_minor', 'currency', 'direction', 'status', 'description',
        'source_snapshot', 'executed_by_user_id', 'executed_at', 'revision',
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
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement accounting posting executions are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting posting executions are append-only.'));
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function handoff(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementAccountingPostingHandoff::class, 'financial_settlement_accounting_posting_handoff_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingEntry::class, 'financial_settlement_accounting_posting_execution_id')->orderBy('sequence_number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingExecutionEvent::class, 'financial_settlement_accounting_posting_execution_id')->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['handoff_revision' => 'integer', 'posting_date' => 'date', 'amount_minor' => 'integer', 'source_snapshot' => 'array', 'executed_at' => 'immutable_datetime', 'revision' => 'integer'];
    }
}
