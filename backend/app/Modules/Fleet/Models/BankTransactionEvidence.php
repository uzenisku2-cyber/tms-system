<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class BankTransactionEvidence extends Model
{
    protected $table = 'bank_transaction_evidence';

    protected $fillable = ['public_id', 'organization_context_id', 'idempotency_key', 'source_type', 'source_reference', 'bank_statement_reference', 'direction', 'booked_at', 'value_date', 'amount', 'currency', 'account_identifier', 'counterparty_name', 'counterparty_account_identifier', 'variable_symbol', 'message', 'evidence_note', 'status', 'recorded_by_user_id', 'recorded_at', 'revision'];

    protected function casts(): array
    {
        return ['booked_at' => 'date', 'value_date' => 'date', 'amount' => 'decimal:2', 'recorded_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Bank transaction evidence is append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Bank transaction evidence is append-only.'));
    }

    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BankTransactionEvidenceEvent::class, 'bank_transaction_evidence_id');
    }

    public function amountBreakdowns(): HasMany
    {
        return $this->hasMany(BankTransactionAmountBreakdown::class, 'bank_transaction_evidence_id')->orderBy('revision');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
