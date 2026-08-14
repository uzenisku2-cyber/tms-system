<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Services\ConditionalPricingRuleEvaluator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ConditionalPricingRuleEvaluatorDecimalNormalizationTest extends TestCase
{
    public function test_decimal_normalization_preserves_integer_zeroes(): void
    {
        $evaluator = new ConditionalPricingRuleEvaluator;

        $method = new ReflectionMethod(
            ConditionalPricingRuleEvaluator::class,
            'normalizeOutputDecimal',
        );

        $method->setAccessible(true);

        $cases = [
            'integer ten' => ['10', '10'],
            'integer one hundred' => ['100', '100'],
            'zero integer' => ['0', '0'],
            'decimal whole number' => ['10.000000', '10'],
            'decimal fraction' => ['10.500000', '10.5'],
            'decimal zero' => ['0.000000', '0'],
            'decimal trailing zeroes' => ['1.230000', '1.23'],
        ];

        foreach ($cases as $label => [$input, $expected]) {
            self::assertSame(
                $expected,
                $method->invoke(
                    $evaluator,
                    $input,
                ),
                $label,
            );
        }
    }
}
