<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PriceListConditionalRule extends Model
{
    public const UPDATED_AT = null;

    public const METRIC_TYPE_RATIO_PERCENTAGE =
        'ratio_percentage';

    public const METRIC_TYPE_QUANTITY =
        'quantity';

    /** @var list<string> */
    public const METRIC_TYPES = [
        self::METRIC_TYPE_RATIO_PERCENTAGE,
        self::METRIC_TYPE_QUANTITY,
    ];

    public const EVALUATION_SCOPE_PER_ROUTE =
        'per_route';

    public const EVALUATION_SCOPE_MONTHLY_DRIVER =
        'monthly_driver';

    public const EVALUATION_SCOPE_MONTHLY_PRICE_LIST =
        'monthly_price_list';

    /** @var list<string> */
    public const EVALUATION_SCOPES = [
        self::EVALUATION_SCOPE_PER_ROUTE,
        self::EVALUATION_SCOPE_MONTHLY_DRIVER,
        self::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
    ];

    public const SOURCE_LOADED_PARCELS =
        'loaded_parcels';

    public const SOURCE_DELIVERED_PARCELS =
        'delivered_parcels';

    public const SOURCE_REDIRECTED_PARCELS =
        'redirected_parcels';

    public const SOURCE_CUSTOMER_REJECTED_PARCELS =
        'customer_rejected_parcels';

    public const SOURCE_NOT_DELIVERED_PARCELS =
        'not_delivered_parcels';

    public const SOURCE_PROCESSED_PARCELS =
        'processed_parcels';

    public const SOURCE_ACTUAL_KM =
        'actual_km';

    public const SOURCE_PLANNED_KM =
        'planned_km';

    /** @var list<string> */
    public const METRIC_SOURCES = [
        self::SOURCE_LOADED_PARCELS,
        self::SOURCE_DELIVERED_PARCELS,
        self::SOURCE_REDIRECTED_PARCELS,
        self::SOURCE_CUSTOMER_REJECTED_PARCELS,
        self::SOURCE_NOT_DELIVERED_PARCELS,
        self::SOURCE_PROCESSED_PARCELS,
        self::SOURCE_ACTUAL_KM,
        self::SOURCE_PLANNED_KM,
    ];

    public const REWARD_METHOD_AMOUNT_PER_UNIT =
        'amount_per_unit';

    public const REWARD_METHOD_FIXED_AMOUNT =
        'fixed_amount';

    public const REWARD_METHOD_PERCENTAGE_OF_ITEM =
        'percentage_of_item';

    /** @var list<string> */
    public const REWARD_METHODS = [
        self::REWARD_METHOD_AMOUNT_PER_UNIT,
        self::REWARD_METHOD_FIXED_AMOUNT,
        self::REWARD_METHOD_PERCENTAGE_OF_ITEM,
    ];

    public const ROUNDING_METHOD_HALF_UP =
        'half_up';

    protected $table =
        'price_list_conditional_rules';

    /** @var list<string> */
    protected $fillable = [
        'price_list_version_id',
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

    /** @return BelongsTo<PriceListVersion, $this> */
    public function priceListVersion(): BelongsTo
    {
        return $this->belongsTo(
            PriceListVersion::class,
        );
    }

    /**
     * @return HasMany<PriceListConditionalBand, $this>
     */
    public function bands(): HasMany
    {
        return $this->hasMany(
            PriceListConditionalBand::class,
            'price_list_conditional_rule_id',
        )->orderBy('position');
    }

    /**
     * @return HasMany<PriceListConditionalRuleMetricComponent, $this>
     */
    public function metricComponents(): HasMany
    {
        return $this->hasMany(
            PriceListConditionalRuleMetricComponent::class,
            'price_list_conditional_rule_id',
        )->orderBy('position');
    }

    /**
     * @return HasMany<PriceListConditionalRuleMetricComponent, $this>
     */
    public function numeratorComponents(): HasMany
    {
        return $this->metricComponents()->where(
            'component_role',
            PriceListConditionalRuleMetricComponent::ROLE_NUMERATOR,
        );
    }

    /**
     * @return HasMany<PriceListConditionalRuleMetricComponent, $this>
     */
    public function denominatorComponents(): HasMany
    {
        return $this->metricComponents()->where(
            'component_role',
            PriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR,
        );
    }

    /**
     * @return HasMany<PriceListConditionalRuleRewardComponent, $this>
     */
    public function rewardComponents(): HasMany
    {
        return $this->hasMany(
            PriceListConditionalRuleRewardComponent::class,
            'price_list_conditional_rule_id',
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
