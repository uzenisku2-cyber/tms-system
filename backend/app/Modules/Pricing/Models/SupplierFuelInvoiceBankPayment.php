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

final class SupplierFuelInvoiceBankPayment extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVERSED = 'reversed';

    /** @var list<string> */
    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id',
        'bank_transaction_evidence_id', 'bank_transaction_evidence_revision',
        'idempotency_key', 'command_fingerprint', 'allocated_amount_minor',
        'currency', 'status', 'reason', 'matched_by_user_id', 'matched_at',
        'reversed_by_user_id', 'reversed_at', 'reversal_reason', 'revision',
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

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by_user_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupplierFuelInvoiceBankPaymentEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'bank_transaction_evidence_revision' => 'integer',
            'allocated_amount_minor' => 'integer', 'revision' => 'integer',
            'matched_at' => 'immutable_datetime', 'reversed_at' => 'immutable_datetime',
        ];
    }
}
