<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverPriceListConditionalRuleRewardComponent extends Model
{
    public const UPDATED_AT = null;

    protected $table =
        'driver_price_list_conditional_rule_reward_components';

    /** @var list<string> */
    protected $fillable = [
        'driver_price_list_conditional_rule_id',
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
