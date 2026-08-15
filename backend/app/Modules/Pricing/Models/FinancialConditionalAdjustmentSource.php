<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FinancialConditionalAdjustmentSource extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'financial_conditional_adjustment_sources';

    /** @var list<string> */
    protected $fillable = [
        'financial_conditional_adjustment_id',
        'financial_calculation_id',
        'source_position',
        'created_at',
    ];

    /** @return BelongsTo<FinancialConditionalAdjustment, $this> */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(
            FinancialConditionalAdjustment::class,
            'financial_conditional_adjustment_id',
        );
    }

    /** @return BelongsTo<FinancialCalculation, $this> */
    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(FinancialCalculation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source_position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
