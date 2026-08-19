<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverPriceListConditionalRuleMetricComponent extends Model
{
    public const UPDATED_AT = null;

    public const ROLE_NUMERATOR =
        PriceListConditionalRuleMetricComponent::ROLE_NUMERATOR;

    public const ROLE_DENOMINATOR =
        PriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR;

    /** @var list<string> */
    public const ROLES =
        PriceListConditionalRuleMetricComponent::ROLES;

    protected $table =
        'driver_price_list_conditional_rule_metric_components';

    /** @var list<string> */
    protected $fillable = [
        'driver_price_list_conditional_rule_id',
        'component_role',
        'metric_source',
        'position',
        'created_at',
    ];

    /**
     * @return BelongsTo<DriverPriceListConditionalRule, $this>
     */
    public function conditionalRule(): BelongsTo
    {
        return $this->belongsTo(
            DriverPriceListConditionalRule::class,
            'driver_price_list_conditional_rule_id',
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
