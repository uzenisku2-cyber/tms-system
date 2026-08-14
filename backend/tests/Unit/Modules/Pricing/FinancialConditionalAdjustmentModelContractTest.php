<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialConditionalAdjustment;
use App\Modules\Pricing\Models\FinancialConditionalAdjustmentSource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

final class FinancialConditionalAdjustmentModelContractTest extends TestCase
{
    public function test_adjustment_model_exposes_scope_audit_relations(): void
    {
        $model = new FinancialConditionalAdjustment;

        self::assertSame(
            'financial_conditional_adjustments',
            $model->getTable(),
        );

        self::assertSame(
            'public_id',
            $model->getRouteKeyName(),
        );

        self::assertSame(
            ['public_id'],
            $model->uniqueIds(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->organization(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->organizationRelationship(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->priceList(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->priceListVersion(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->conditionalRule(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->conditionalBand(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->performedByDriver(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->calculatedBy(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->supersedesAdjustment(),
        );

        self::assertInstanceOf(
            HasMany::class,
            $model->supersededByAdjustments(),
        );

        self::assertInstanceOf(
            HasMany::class,
            $model->sources(),
        );
    }

    public function test_source_model_links_adjustment_to_route_calculation(): void
    {
        $model = new FinancialConditionalAdjustmentSource;

        self::assertSame(
            'financial_conditional_adjustment_sources',
            $model->getTable(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->adjustment(),
        );

        self::assertInstanceOf(
            BelongsTo::class,
            $model->financialCalculation(),
        );
    }

    public function test_immutable_audit_fields_are_mass_assignable(): void
    {
        $fillable =
            (new FinancialConditionalAdjustment)
                ->getFillable();

        foreach ([
            'evaluation_scope',
            'period_start',
            'period_end',
            'calculation_version',
            'metric_numerator_value',
            'metric_denominator_value',
            'metric_value',
            'reward_quantity_value',
            'reward_target_item_amount',
            'adjustment_value',
            'conditional_amount',
            'evaluation_snapshot',
            'supersedes_adjustment_id',
        ] as $field) {
            self::assertContains(
                $field,
                $fillable,
            );
        }
    }
}
