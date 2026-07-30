<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Models\FinancialCalculation;
use DomainException;
use InvalidArgumentException;

final class FinancialCalculationWorkflow
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
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

    public function assertCanTransition(
        string $fromStatus,
        string $toStatus,
    ): void {
        if (
            ! $this->canTransition(
                $fromStatus,
                $toStatus,
            )
        ) {
            throw new DomainException(
                sprintf(
                    'Financial calculation transition from "%s" to "%s" is not allowed.',
                    $fromStatus,
                    $toStatus,
                ),
            );
        }
    }

    public function canTransition(
        string $fromStatus,
        string $toStatus,
    ): bool {
        $this->assertKnownStatus(
            $fromStatus,
            'Source',
        );

        $this->assertKnownStatus(
            $toStatus,
            'Target',
        );

        return in_array(
            $toStatus,
            self::TRANSITIONS[$fromStatus],
            true,
        );
    }

    /**
     * @return list<string>
     */
    public function allowedNextStatuses(
        string $status,
    ): array {
        $this->assertKnownStatus(
            $status,
            'Financial calculation',
        );

        return self::TRANSITIONS[$status];
    }

    private function assertKnownStatus(
        string $status,
        string $label,
    ): void {
        if (
            ! in_array(
                $status,
                FinancialCalculation::STATUSES,
                true,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s status "%s" is not supported.',
                    $label,
                    $status,
                ),
            );
        }
    }
}
