<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CustomerInvoiceDeliveryEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id', 'customer_invoice_pdf_artifact_id',
        'revision', 'idempotency_key', 'command_fingerprint', 'pdf_sha256', 'method', 'recipient',
        'delivered_at', 'evidence_reference', 'reason', 'actor_user_id', 'recorded_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'delivered_at' => 'immutable_datetime', 'recorded_at' => 'immutable_datetime'];
    }
}
