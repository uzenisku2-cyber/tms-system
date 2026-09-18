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
use RuntimeException;

final class FinancialSettlementAccountingPostingHandoff extends Model
{
    use HasUuids;

    public const STATUS_PREPARED = 'prepared';

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_bank_payment_reconciliation_id',
        'financial_settlement_bank_payment_id', 'financial_settlement_statement_id', 'billing_document_id',
        'bank_transaction_evidence_id', 'idempotency_key', 'command_fingerprint',
        'reconciliation_revision', 'payment_revision', 'statement_revision', 'bank_transaction_evidence_revision',
        'posting_date', 'accounting_reference', 'amount_minor', 'currency', 'direction', 'status', 'reason',
        'source_snapshot', 'prepared_by_user_id', 'prepared_at', 'revision',
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
        self::updating(static fn (): never => throw new RuntimeException('Financial settlement accounting posting handoffs are append-only.'));
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting posting handoffs are append-only.'));
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankPaymentReconciliation::class, 'financial_settlement_bank_payment_reconciliation_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankPayment::class, 'financial_settlement_bank_payment_id');
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

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPostingHandoffEvent::class, 'financial_settlement_accounting_posting_handoff_id')->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'reconciliation_revision' => 'integer', 'payment_revision' => 'integer',
            'statement_revision' => 'integer', 'bank_transaction_evidence_revision' => 'integer',
            'posting_date' => 'date', 'amount_minor' => 'integer', 'source_snapshot' => 'array',
            'prepared_at' => 'immutable_datetime', 'revision' => 'integer',
        ];
    }
}
