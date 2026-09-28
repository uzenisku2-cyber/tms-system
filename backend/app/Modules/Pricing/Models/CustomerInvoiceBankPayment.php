<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CustomerInvoiceBankPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id', 'bank_transaction_evidence_id',
        'bank_transaction_evidence_revision', 'idempotency_key', 'command_fingerprint',
        'allocated_amount_minor', 'currency', 'status', 'reason', 'allocated_by_user_id',
        'allocated_at', 'reversed_by_user_id', 'reversed_at', 'reversal_reason', 'revision',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return [
            'bank_transaction_evidence_revision' => 'integer', 'allocated_amount_minor' => 'integer',
            'revision' => 'integer', 'allocated_at' => 'immutable_datetime', 'reversed_at' => 'immutable_datetime',
        ];
    }
}
