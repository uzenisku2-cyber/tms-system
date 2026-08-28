<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelSurcharge extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'public_id',
        'owner_organization_id',
        'customer_relationship_id',
        'billing_rate_per_actual_km',
        'currency',
        'valid_from',
        'valid_until',
        'status',
        'lock_version',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'billing_rate_per_actual_km' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'lock_version' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function recipientRates(): HasMany
    {
        return $this->hasMany(FuelSurchargeRecipientRate::class);
    }
}
