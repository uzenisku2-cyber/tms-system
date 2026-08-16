<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PriceListConditionalRuleMetricComponent extends Model
{
    public const UPDATED_AT = null;

    public const ROLE_NUMERATOR = 'numerator';

    public const ROLE_DENOMINATOR = 'denominator';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_NUMERATOR,
        self::ROLE_DENOMINATOR,
    ];

    protected $table =
        'price_list_conditional_rule_metric_components';

    /** @var list<string> */
    protected $fillable = [
        'price_list_conditional_rule_id',
        'component_role',
        'metric_source',
        'position',
        'created_at',
    ];

    /**
     * @return BelongsTo<PriceListConditionalRule, $this>
     */
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
