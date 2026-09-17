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

final class FinancialSettlementBankPaymentReconciliation extends Model
{
    use HasUuids;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_OPEN = 'open';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'owner_organization_id', 'financial_settlement_bank_payment_id',
        'financial_settlement_statement_id', 'bank_transaction_evidence_id', 'payment_revision',
        'status', 'revision', 'reason', 'confirmed_by_user_id', 'confirmed_at',
        'reopened_by_user_id', 'reopened_at',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementBankPayment::class, 'financial_settlement_bank_payment_id');
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlementStatement::class, 'financial_settlement_statement_id');
    }

    public function bankTransactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementBankPaymentReconciliationEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'payment_revision' => 'integer', 'revision' => 'integer',
            'confirmed_at' => 'immutable_datetime', 'reopened_at' => 'immutable_datetime',
        ];
    }
}
