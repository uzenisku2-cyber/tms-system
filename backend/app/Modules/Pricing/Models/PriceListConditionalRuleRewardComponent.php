<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PriceListConditionalRuleRewardComponent extends Model
{
    public const UPDATED_AT = null;

    protected $table =
        'price_list_conditional_rule_reward_components';

    /** @var list<string> */
    protected $fillable = [
        'price_list_conditional_rule_id',
        'metric_source',
        'position',
        'created_at',
    ];

    /** @return BelongsTo<PriceListConditionalRule, $this> */
    public function conditionalRule(): BelongsTo
    {
        return $this->belongsTo(
            PriceListConditionalRule::class,
            'price_list_conditional_rule_id',
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
