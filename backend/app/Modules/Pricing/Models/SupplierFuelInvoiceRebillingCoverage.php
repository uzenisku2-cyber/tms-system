<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplierFuelInvoiceRebillingCoverage extends Model
{
    public const STATUS_COMPLETE = 'complete';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_NEGATIVE_MARGIN = 'negative_margin';

    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id', 'idempotency_key',
        'command_fingerprint', 'comparison_basis', 'currency', 'purchase_amount_minor',
        'rebilled_amount_minor', 'unrebilled_amount_minor', 'margin_minor', 'status',
        'source_snapshot', 'revision', 'evaluated_by_user_id', 'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_amount_minor' => 'integer', 'rebilled_amount_minor' => 'integer',
            'unrebilled_amount_minor' => 'integer', 'margin_minor' => 'integer',
            'source_snapshot' => 'array', 'revision' => 'integer', 'evaluated_at' => 'immutable_datetime',
        ];
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierFuelInvoiceRebillingCoverageLine::class, 'coverage_id')->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupplierFuelInvoiceRebillingCoverageEvent::class, 'coverage_id')->orderBy('revision');
    }

    public function evaluatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by_user_id');
    }
}
