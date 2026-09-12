<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierFuelInvoiceRebillingCoverageEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'public_id', 'coverage_id', 'revision', 'event_type', 'payload',
        'actor_user_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'payload' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceRebillingCoverage::class, 'coverage_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
