<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Fuel\Models\FuelTransaction;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SupplierFuelInvoiceTransactionAllocation extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'public_id', 'owner_organization_id', 'billing_document_id', 'fuel_transaction_id',
        'idempotency_key', 'command_fingerprint', 'allocated_amount_minor', 'currency',
        'status', 'revision', 'reason', 'created_by_user_id', 'reversed_by_user_id', 'reversed_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return ['allocated_amount_minor' => 'integer', 'revision' => 'integer', 'reversed_at' => 'immutable_datetime'];
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function fuelTransaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SupplierFuelInvoiceTransactionAllocationEvent::class, 'allocation_id')->orderBy('revision');
    }
}
