<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Services\FinancialCalculationWorkflow;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FinancialCalculationWorkflowTest extends TestCase
{
    private FinancialCalculationWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = new FinancialCalculationWorkflow;
    }

    public function test_it_allows_the_configured_transitions(): void
    {
        /** @var list<array{0: string, 1: string}> $transitions */
        $transitions = [
            [
                FinancialCalculation::STATUS_CALCULATED,
                FinancialCalculation::STATUS_UNDER_REVIEW,
            ],
            [
                FinancialCalculation::STATUS_CALCULATED,
                FinancialCalculation::STATUS_CANCELLED,
            ],
            [
                FinancialCalculation::STATUS_UNDER_REVIEW,
                FinancialCalculation::STATUS_APPROVED,
            ],
            [
                FinancialCalculation::STATUS_UNDER_REVIEW,
                FinancialCalculation::STATUS_CANCELLED,
            ],
            [
                FinancialCalculation::STATUS_APPROVED,
                FinancialCalculation::STATUS_CLOSED,
            ],
        ];

        foreach ($transitions as [$fromStatus, $toStatus]) {
            self::assertTrue(
                $this->workflow->canTransition(
                    $fromStatus,
                    $toStatus,
                ),
            );

            $this->workflow->assertCanTransition(
                $fromStatus,
                $toStatus,
            );
        }
    }

    public function test_it_rejects_disallowed_transitions(): void
    {
        /** @var list<array{0: string, 1: string}> $transitions */
        $transitions = [
            [
                FinancialCalculation::STATUS_CALCULATED,
                FinancialCalculation::STATUS_APPROVED,
            ],
            [
                FinancialCalculation::STATUS_CALCULATED,
                FinancialCalculation::STATUS_CLOSED,
            ],
            [
                FinancialCalculation::STATUS_UNDER_REVIEW,
                FinancialCalculation::STATUS_CLOSED,
            ],
            [
                FinancialCalculation::STATUS_APPROVED,
                FinancialCalculation::STATUS_CANCELLED,
            ],
            [
                FinancialCalculation::STATUS_CLOSED,
                FinancialCalculation::STATUS_CALCULATED,
            ],
            [
                FinancialCalculation::STATUS_CANCELLED,
                FinancialCalculation::STATUS_CALCULATED,
            ],
        ];

        foreach ($transitions as [$fromStatus, $toStatus]) {
            self::assertFalse(
                $this->workflow->canTransition(
                    $fromStatus,
                    $toStatus,
                ),
            );

            try {
                $this->workflow->assertCanTransition(
                    $fromStatus,
                    $toStatus,
                );

                self::fail(
                    sprintf(
                        'Transition from "%s" to "%s" was accepted.',
                        $fromStatus,
                        $toStatus,
                    ),
                );
            } catch (DomainException $exception) {
                self::assertSame(
                    sprintf(
                        'Financial calculation transition from "%s" to "%s" is not allowed.',
                        $fromStatus,
                        $toStatus,
                    ),
                    $exception->getMessage(),
                );
            }
        }
    }

    public function test_it_returns_allowed_next_statuses_for_every_state(): void
    {
        /** @var array<string, list<string>> $expected */
        $expected = [
            FinancialCalculation::STATUS_CALCULATED => [
                FinancialCalculation::STATUS_UNDER_REVIEW,
                FinancialCalculation::STATUS_CANCELLED,
            ],

            FinancialCalculation::STATUS_UNDER_REVIEW => [
                FinancialCalculation::STATUS_APPROVED,
                FinancialCalculation::STATUS_CANCELLED,
            ],

            FinancialCalculation::STATUS_APPROVED => [
                FinancialCalculation::STATUS_CLOSED,
            ],

            FinancialCalculation::STATUS_CLOSED => [],

            FinancialCalculation::STATUS_CANCELLED => [],
        ];

        foreach ($expected as $status => $nextStatuses) {
            self::assertSame(
                $nextStatuses,
                $this->workflow->allowedNextStatuses(
                    $status,
                ),
            );
        }
    }

    public function test_it_rejects_unknown_statuses(): void
    {
        try {
            $this->workflow->canTransition(
                'unknown-source',
                FinancialCalculation::STATUS_CALCULATED,
            );

            self::fail(
                'An unknown source status was accepted.',
            );
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Source status "unknown-source" is not supported.',
                $exception->getMessage(),
            );
        }

        try {
            $this->workflow->canTransition(
                FinancialCalculation::STATUS_CALCULATED,
                'unknown-target',
            );

            self::fail(
                'An unknown target status was accepted.',
            );
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Target status "unknown-target" is not supported.',
                $exception->getMessage(),
            );
        }

        try {
            $this->workflow->allowedNextStatuses(
                'unknown-status',
            );

            self::fail(
                'An unknown workflow status was accepted.',
            );
        } catch (InvalidArgumentException $exception) {
            self::assertSame(
                'Financial calculation status "unknown-status" is not supported.',
                $exception->getMessage(),
            );
        }
    }
}
