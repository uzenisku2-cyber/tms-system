<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BillingDocumentCommercialIdentity extends Model
{
    use HasUuids;

    public const DIRECTION_RECEIVABLE = 'receivable';

    public const DIRECTION_PAYABLE = 'payable';

    protected $fillable = [
        'public_id', 'billing_document_id', 'direction', 'document_number',
        'variable_symbol', 'issued_on', 'taxable_supply_on', 'due_on',
        'counterparty_name', 'counterparty_registration_number',
        'counterparty_vat_number', 'counterparty_account_identifier',
        'counterparty_snapshot', 'revision', 'idempotency_key',
        'command_fingerprint', 'owner_organization_id', 'created_by_user_id',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return [
            'issued_on' => 'immutable_date',
            'taxable_supply_on' => 'immutable_date',
            'due_on' => 'immutable_date',
            'counterparty_snapshot' => 'array',
            'revision' => 'integer',
        ];
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BillingDocumentCommercialIdentityEvent::class)
            ->orderBy('revision');
    }
}
