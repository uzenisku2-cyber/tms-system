<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PriceListConditionalBand extends Model
{
    public const UPDATED_AT = null;

    protected $table =
        'price_list_conditional_bands';

    /** @var list<string> */
    protected $fillable = [
        'price_list_conditional_rule_id',
        'minimum_value',
        'maximum_value',
        'minimum_inclusive',
        'maximum_inclusive',
        'adjustment_value',
        'position',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'minimum_inclusive' => true,
        'maximum_inclusive' => false,
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
            'minimum_value' => 'decimal:4',
            'maximum_value' => 'decimal:4',
            'minimum_inclusive' => 'boolean',
            'maximum_inclusive' => 'boolean',
            'adjustment_value' => 'decimal:4',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
