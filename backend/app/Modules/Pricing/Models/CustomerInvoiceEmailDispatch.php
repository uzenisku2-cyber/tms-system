<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CustomerInvoiceEmailDispatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id', 'customer_invoice_pdf_artifact_id',
        'pdf_sha256', 'idempotency_key', 'command_fingerprint', 'recipient_email', 'reason', 'status',
        'requested_by_user_id', 'queued_at', 'started_at', 'accepted_at', 'uncertain_at', 'failure_summary',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return [
            'queued_at' => 'immutable_datetime', 'started_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime', 'uncertain_at' => 'immutable_datetime',
        ];
    }
}
