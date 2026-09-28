<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomerInvoicePdfArtifact extends Model
{
    protected $fillable = [
        'owner_organization_id', 'billing_document_id', 'commercial_identity_id',
        'snapshot_sha256', 'pdf_sha256', 'storage_path', 'byte_size',
        'generated_at', 'generated_by_user_id',
    ];

    protected function casts(): array
    {
        return ['byte_size' => 'integer', 'generated_at' => 'immutable_datetime'];
    }
}
