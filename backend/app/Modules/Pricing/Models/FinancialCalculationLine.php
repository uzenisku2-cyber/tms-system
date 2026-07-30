<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCalculationLine extends Model
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
    public const PRICING_CODES = [
        self::CODE_DELIVERED_PARCELS,
        self::CODE_REDIRECTED_PARCELS,
        self::CODE_UNDELIVERED_PARCELS,
        self::CODE_ACTUAL_KM,
    ];

    public const UNIT_PARCEL =
        PriceListItem::UNIT_PARCEL;

    public const UNIT_KM =
        PriceListItem::UNIT_KM;

    /** @var list<string> */
    public const UNITS = [
        self::UNIT_PARCEL,
        self::UNIT_KM,
    ];

    public const ROUNDING_METHOD_HALF_UP =
        PriceListItem::ROUNDING_METHOD_HALF_UP;

    protected $table = 'financial_calculation_lines';

    /** @var list<string> */
    protected $fillable = [
        'financial_calculation_id',
        'price_list_item_id',
        'pricing_code',
        'description',
        'quantity',
        'unit',
        'unit_rate',
        'currency',
        'line_amount',
        'source_field',
        'rounding_scale',
        'rounding_method',
        'position',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'rounding_scale' => 2,
        'rounding_method' => self::ROUNDING_METHOD_HALF_UP,
    ];

    /**
     * @return BelongsTo<FinancialCalculation, $this>
     */
    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCalculation::class,
        );
    }

    /** @return BelongsTo<PriceListItem, $this> */
    public function priceListItem(): BelongsTo
    {
        return $this->belongsTo(
            PriceListItem::class,
        );
    }

    public function isParcelLine(): bool
    {
        return $this->unit === self::UNIT_PARCEL;
    }

    public function isKilometreLine(): bool
    {
        return $this->unit === self::UNIT_KM;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_rate' => 'decimal:4',
            'line_amount' => 'decimal:2',
            'rounding_scale' => 'integer',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
