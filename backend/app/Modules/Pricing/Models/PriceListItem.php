<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    public const UPDATED_AT = null;

    public const CODE_DELIVERED_PARCELS =
        'delivered_parcels';

    public const CODE_REDIRECTED_PARCELS =
        'redirected_parcels';

    public const CODE_UNDELIVERED_PARCELS =
        'undelivered_parcels';

    public const CODE_ACTUAL_KM =
        'actual_km';

    /** @var list<string> */
    public const CODES = [
        self::CODE_DELIVERED_PARCELS,
        self::CODE_REDIRECTED_PARCELS,
        self::CODE_UNDELIVERED_PARCELS,
        self::CODE_ACTUAL_KM,
    ];

    public const CALCULATION_METHOD_QUANTITY_TIMES_RATE =
        'quantity_times_rate';

    /** @var list<string> */
    public const CALCULATION_METHODS = [
        self::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
    ];

    public const UNIT_PARCEL = 'parcel';

    public const UNIT_KM = 'km';

    /** @var list<string> */
    public const UNITS = [
        self::UNIT_PARCEL,
        self::UNIT_KM,
    ];

    public const QUANTITY_SOURCE_DELIVERED_PARCELS =
        'delivered_parcels';

    public const QUANTITY_SOURCE_REDIRECTED_PARCELS =
        'redirected_parcels';

    public const QUANTITY_SOURCE_UNDELIVERED_PARCELS =
        'undelivered_parcels';

    public const QUANTITY_SOURCE_ACTUAL_KM =
        'actual_km';

    /** @var list<string> */
    public const QUANTITY_SOURCES = [
        self::QUANTITY_SOURCE_DELIVERED_PARCELS,
        self::QUANTITY_SOURCE_REDIRECTED_PARCELS,
        self::QUANTITY_SOURCE_UNDELIVERED_PARCELS,
        self::QUANTITY_SOURCE_ACTUAL_KM,
    ];

    public const ROUNDING_METHOD_HALF_UP =
        'half_up';

    /** @var list<string> */
    public const ROUNDING_METHODS = [
        self::ROUNDING_METHOD_HALF_UP,
    ];

    protected $table = 'price_list_items';

    /** @var list<string> */
    protected $fillable = [
        'price_list_version_id',
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

    /** @return BelongsTo<PriceListVersion, $this> */
    public function priceListVersion(): BelongsTo
    {
        return $this->belongsTo(
            PriceListVersion::class,
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
