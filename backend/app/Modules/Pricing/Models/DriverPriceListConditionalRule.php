<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DriverPriceListConditionalRule extends Model
{
    public const UPDATED_AT = null;

    public const METRIC_TYPE_RATIO_PERCENTAGE =
        PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE;

    public const METRIC_TYPE_QUANTITY =
        PriceListConditionalRule::METRIC_TYPE_QUANTITY;

    /** @var list<string> */
    public const METRIC_TYPES =
        PriceListConditionalRule::METRIC_TYPES;

    public const EVALUATION_SCOPE_PER_ROUTE =
        PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE;

    public const EVALUATION_SCOPE_MONTHLY_DRIVER =
        PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER;

    public const EVALUATION_SCOPE_MONTHLY_PRICE_LIST =
        PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST;

    /** @var list<string> */
    public const EVALUATION_SCOPES =
        PriceListConditionalRule::EVALUATION_SCOPES;

    public const SOURCE_LOADED_PARCELS =
        PriceListConditionalRule::SOURCE_LOADED_PARCELS;

    public const SOURCE_DELIVERED_PARCELS =
        PriceListConditionalRule::SOURCE_DELIVERED_PARCELS;

    public const SOURCE_REDIRECTED_PARCELS =
        PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS;

    public const SOURCE_CUSTOMER_REJECTED_PARCELS =
        PriceListConditionalRule::SOURCE_CUSTOMER_REJECTED_PARCELS;

    public const SOURCE_NOT_DELIVERED_PARCELS =
        PriceListConditionalRule::SOURCE_NOT_DELIVERED_PARCELS;

    public const SOURCE_PROCESSED_PARCELS =
        PriceListConditionalRule::SOURCE_PROCESSED_PARCELS;

    public const SOURCE_ACTUAL_KM =
        PriceListConditionalRule::SOURCE_ACTUAL_KM;

    public const SOURCE_PLANNED_KM =
        PriceListConditionalRule::SOURCE_PLANNED_KM;

    /** @var list<string> */
    public const METRIC_SOURCES =
        PriceListConditionalRule::METRIC_SOURCES;

    public const REWARD_METHOD_AMOUNT_PER_UNIT =
        PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT;

    public const REWARD_METHOD_FIXED_AMOUNT =
        PriceListConditionalRule::REWARD_METHOD_FIXED_AMOUNT;

    public const REWARD_METHOD_PERCENTAGE_OF_ITEM =
        PriceListConditionalRule::REWARD_METHOD_PERCENTAGE_OF_ITEM;

    /** @var list<string> */
    public const REWARD_METHODS =
        PriceListConditionalRule::REWARD_METHODS;

    public const ROUNDING_METHOD_HALF_UP =
        PriceListConditionalRule::ROUNDING_METHOD_HALF_UP;

    protected $table =
        'driver_price_list_conditional_rules';

    /** @var list<string> */
    protected $fillable = [
        'driver_price_list_version_id',
        'code',
        'name',
        'description',
        'metric_type',
        'metric_numerator_source',
        'metric_denominator_source',
        'evaluation_scope',
        'reward_method',
        'reward_quantity_source',
        'reward_target_item_code',
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

    /** @return BelongsTo<DriverPriceListVersion, $this> */
    public function driverPriceListVersion(): BelongsTo
    {
        return $this->belongsTo(
            DriverPriceListVersion::class,
        );
    }

    /**
     * @return HasMany<DriverPriceListConditionalBand, $this>
     */
    public function bands(): HasMany
    {
        return $this->hasMany(
            DriverPriceListConditionalBand::class,
            'driver_price_list_conditional_rule_id',
        )->orderBy('position');
    }

    /**
     * @return HasMany<DriverPriceListConditionalRuleMetricComponent, $this>
     */
    public function metricComponents(): HasMany
    {
        return $this->hasMany(
            DriverPriceListConditionalRuleMetricComponent::class,
            'driver_price_list_conditional_rule_id',
        )->orderBy('position');
    }

    /**
     * @return HasMany<DriverPriceListConditionalRuleMetricComponent, $this>
     */
    public function numeratorComponents(): HasMany
    {
        return $this->metricComponents()->where(
            'component_role',
            DriverPriceListConditionalRuleMetricComponent::ROLE_NUMERATOR,
        );
    }

    /**
     * @return HasMany<DriverPriceListConditionalRuleMetricComponent, $this>
     */
    public function denominatorComponents(): HasMany
    {
        return $this->metricComponents()->where(
            'component_role',
            DriverPriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR,
        );
    }

    /**
     * @return HasMany<DriverPriceListConditionalRuleRewardComponent, $this>
     */
    public function rewardComponents(): HasMany
    {
        return $this->hasMany(
            DriverPriceListConditionalRuleRewardComponent::class,
            'driver_price_list_conditional_rule_id',
        )->orderBy('position');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rounding_scale' => 'integer',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
