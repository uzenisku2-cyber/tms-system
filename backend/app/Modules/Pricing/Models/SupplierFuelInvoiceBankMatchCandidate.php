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

final class SupplierFuelInvoiceBankMatchCandidate extends Model
{
    use HasUuids;

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUPERSEDED = 'superseded';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id',
        'bank_transaction_evidence_id', 'bank_transaction_evidence_revision',
        'idempotency_key', 'candidate_fingerprint', 'currency',
        'bank_amount_minor', 'invoice_unpaid_amount_minor', 'proposed_amount_minor',
        'score_basis_points', 'status', 'match_reasons', 'source_snapshot',
        'revision', 'proposed_by_user_id', 'proposed_at', 'reviewed_by_user_id',
        'reviewed_at', 'review_reason', 'supplier_fuel_invoice_bank_payment_id',
        'materialized_by_user_id', 'materialized_at',
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

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function bankTransactionEvidence(): BelongsTo
    {
        return $this->belongsTo(BankTransactionEvidence::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceBankPayment::class, 'supplier_fuel_invoice_bank_payment_id');
    }

    public function materializedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'materialized_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupplierFuelInvoiceBankMatchCandidateEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'bank_transaction_evidence_revision' => 'integer',
            'bank_amount_minor' => 'integer', 'invoice_unpaid_amount_minor' => 'integer',
            'proposed_amount_minor' => 'integer', 'score_basis_points' => 'integer',
            'match_reasons' => 'array', 'source_snapshot' => 'array', 'revision' => 'integer',
            'proposed_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime',
            'materialized_at' => 'immutable_datetime',
        ];
    }
}
