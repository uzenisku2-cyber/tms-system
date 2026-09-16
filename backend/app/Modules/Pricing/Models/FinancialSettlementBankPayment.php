<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialSettlementBankPayment extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVERSED = 'reversed';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_bank_match_candidate_id',
        'financial_settlement_statement_id', 'billing_document_id', 'bank_transaction_evidence_id',
        'bank_transaction_evidence_revision', 'idempotency_key', 'command_fingerprint',
        'allocated_amount_minor', 'currency', 'status', 'reason', 'allocated_by_user_id',
        'allocated_at', 'reversed_by_user_id', 'reversed_at', 'reversal_reason', 'revision',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankMatchCandidate::class, 'financial_settlement_bank_match_candidate_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementStatement::class, 'financial_settlement_statement_id');
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function bankTransactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_user_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementBankPaymentEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'bank_transaction_evidence_revision' => 'integer',
            'allocated_amount_minor' => 'integer',
            'allocated_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
            'revision' => 'integer',
        ];
    }
}
