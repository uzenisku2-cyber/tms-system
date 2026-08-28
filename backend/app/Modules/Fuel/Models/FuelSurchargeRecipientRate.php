<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelSurchargeRecipientRate extends Model
{
    public const TYPE_OWN_DRIVER = 'own_driver';
    public const TYPE_EXTERNAL_CARRIER = 'external_carrier';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'public_id',
        'fuel_surcharge_id',
        'recipient_type',
        'driver_organization_assignment_id',
        'carrier_relationship_id',
        'payout_rate_per_actual_km',
        'valid_from',
        'valid_until',
        'status',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'payout_rate_per_actual_km' => 'decimal:4',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function fuelSurcharge(): BelongsTo
    {
        return $this->belongsTo(FuelSurcharge::class);
    }
}
