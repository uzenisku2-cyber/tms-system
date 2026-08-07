<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use Tests\TestCase;

final class FinancialCalculationRecalculationEventFoundationTest extends TestCase
{
    public function test_recalculated_event_contract_is_registered(): void
    {
        self::assertSame(
            'recalculated',
            FinancialCalculationEvent::TYPE_RECALCULATED,
        );

        self::assertContains(
            FinancialCalculationEvent::TYPE_RECALCULATED,
            FinancialCalculationEvent::EVENT_TYPES,
        );

        $event = new FinancialCalculationEvent([
            'event_type' => FinancialCalculationEvent::TYPE_RECALCULATED,
            'from_status' => FinancialCalculation::STATUS_APPROVED,
            'to_status' => FinancialCalculation::STATUS_CALCULATED,
        ]);

        self::assertTrue($event->isRecalculationEvent());
        self::assertFalse($event->isInitialCalculationEvent());
    }
}
