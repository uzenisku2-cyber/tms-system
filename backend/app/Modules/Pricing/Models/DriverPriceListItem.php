<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverPriceListItem extends Model
{
    public const UPDATED_AT = null;

    public const CODE_DELIVERED_PARCELS =
        PriceListItem::CODE_DELIVERED_PARCELS;

    public const CODE_REDIRECTED_PARCELS =
        PriceListItem::CODE_REDIRECTED_PARCELS;

    public const CODE_UNDELIVERED_PARCELS =
        PriceListItem::CODE_UNDELIVERED_PARCELS;

    public const CODE_ACTUAL_KM =
        PriceListItem::CODE_ACTUAL_KM;

    /** @var list<string> */
    public const CODES = [
        self::CODE_DELIVERED_PARCELS,
        self::CODE_REDIRECTED_PARCELS,
        self::CODE_UNDELIVERED_PARCELS,
        self::CODE_ACTUAL_KM,
    ];

    public const CALCULATION_METHOD_QUANTITY_TIMES_RATE =
        PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE;

    public const UNIT_PARCEL = PriceListItem::UNIT_PARCEL;

    public const UNIT_KM = PriceListItem::UNIT_KM;

    public const ROUNDING_METHOD_HALF_UP =
        PriceListItem::ROUNDING_METHOD_HALF_UP;

    protected $table = 'driver_price_list_items';

    /** @var list<string> */
    protected $fillable = [
        'driver_price_list_version_id',
        'code',
        'description',
        'calculation_method',
        'unit',
        'unit_rate',
        'currency',
        'quantity_source',
        'rounding_scale',
        'rounding_method',
        'position',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'calculation_method' => self::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
        'rounding_scale' => 2,
        'rounding_method' => self::ROUNDING_METHOD_HALF_UP,
    ];

    /** @return BelongsTo<DriverPriceListVersion, $this> */
    public function driverPriceListVersion(): BelongsTo
    {
        return $this->belongsTo(
            DriverPriceListVersion::class,
        );
    }

    public function isParcelItem(): bool
    {
        return $this->unit === self::UNIT_PARCEL;
    }

    public function isKilometreItem(): bool
    {
        return $this->unit === self::UNIT_KM;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_rate' => 'decimal:4',
            'rounding_scale' => 'integer',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
