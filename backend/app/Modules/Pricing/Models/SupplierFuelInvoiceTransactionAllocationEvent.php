<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierFuelInvoiceTransactionAllocationEvent extends Model
{
    use HasUuids;

    public const TYPE_ALLOCATED = 'allocated';

    public const TYPE_REVERSED = 'reversed';

    public $timestamps = false;

    protected $fillable = ['public_id', 'allocation_id', 'revision', 'event_type', 'reason', 'evidence', 'actor_user_id', 'occurred_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(SupplierFuelInvoiceTransactionAllocation::class, 'allocation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
