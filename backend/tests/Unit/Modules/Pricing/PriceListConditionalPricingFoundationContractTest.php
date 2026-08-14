<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\PriceListConditionalRule;
use PHPUnit\Framework\TestCase;

final class PriceListConditionalPricingFoundationContractTest extends TestCase
{
    public function test_supported_evaluation_scopes_are_explicit(): void
    {
        self::assertSame(
            [
                'per_route',
                'monthly_driver',
            ],
            PriceListConditionalRule::EVALUATION_SCOPES,
        );
    }

    public function test_supported_metric_types_are_explicit(): void
    {
        self::assertSame(
            [
                'ratio_percentage',
                'quantity',
            ],
            PriceListConditionalRule::METRIC_TYPES,
        );
    }

    public function test_metric_sources_preserve_business_semantics(): void
    {
        self::assertContains(
            'loaded_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertContains(
            'redirected_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertContains(
            'customer_rejected_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertContains(
            'not_delivered_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertContains(
            'processed_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertNotContains(
            'undelivered_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );
    }

    public function test_supported_reward_methods_are_explicit(): void
    {
        self::assertSame(
            [
                'amount_per_unit',
                'fixed_amount',
                'percentage_of_item',
            ],
            PriceListConditionalRule::REWARD_METHODS,
        );
    }

    public function test_v4_redirect_bonus_is_representable_by_configuration(): void
    {
        self::assertContains(
            'ratio_percentage',
            PriceListConditionalRule::METRIC_TYPES,
        );

        self::assertContains(
            'monthly_driver',
            PriceListConditionalRule::EVALUATION_SCOPES,
        );

        self::assertContains(
            'redirected_parcels',
            PriceListConditionalRule::METRIC_SOURCES,
        );

        self::assertContains(
            'amount_per_unit',
            PriceListConditionalRule::REWARD_METHODS,
        );
    }
}
