<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\PriceList;
use DomainException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinancialCalculationWriteService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly FinancialCalculationPersistenceService $persistence,
        private readonly FinancialCalculationLifecycleService $lifecycle,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function createInitial(
        User $actor,
        array $input,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $priceListPublicId =
            $this->requiredString(
                $input,
                'price_list_public_id',
            );

        $priceListVersionNumber =
            $this->requiredPositiveInteger(
                $input,
                'price_list_version',
            );

        $dailyReportPublicId =
            $this->requiredString(
                $input,
                'daily_report_public_id',
            );

        $dailyReportVersionNumber =
            $this->requiredPositiveInteger(
                $input,
                'daily_report_version',
            );

        $priceList = PriceList::query()
            ->where(
                'public_id',
                $priceListPublicId,
            )
            ->where(
                'provider_organization_id',
                $organizationId,
            )
            ->firstOrFail();

        $priceListVersion = $priceList
            ->versions()
            ->where(
                'version_number',
                $priceListVersionNumber,
            )
            ->firstOrFail();

        $customerOrganizationId =
            $this->positiveModelIdentifier(
                $priceList->getAttribute(
                    'customer_organization_id',
                ),
                'Price-list customer organization identifier',
            );

        $dailyReport = DailyReport::query()
            ->where(
                'public_id',
                $dailyReportPublicId,
            )
            ->where(
                'organization_id',
                $customerOrganizationId,
            )
            ->firstOrFail();

        $dailyReportVersion = $dailyReport
            ->versions()
            ->where(
                'version_number',
                $dailyReportVersionNumber,
            )
            ->firstOrFail();

        try {
            return $this->persistence->createInitialCalculation(
                dailyReportVersionId: $this->positiveModelIdentifier(
                    $dailyReportVersion->getKey(),
                    'Daily-report version identifier',
                ),
                priceListVersionId: $this->positiveModelIdentifier(
                    $priceListVersion->getKey(),
                    'Price-list version identifier',
                ),
                calculatedByUserId: $this->positiveModelIdentifier(
                    $actor->getKey(),
                    'Calculating user identifier',
                ),
                calculatedAt: now(),
                reason: $this->nullableString(
                    $input,
                    'reason',
                ),
            );
        } catch (DomainException $exception) {
            throw new ConflictHttpException(
                $exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function startReview(
        User $actor,
        string $financialCalculationPublicId,
        array $input,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $calculation = FinancialCalculation::query()
            ->where(
                'public_id',
                $financialCalculationPublicId,
            )
            ->where(
                'organization_id',
                $organizationId,
            )
            ->firstOrFail();

        try {
            $reviewedCalculation =
                $this->lifecycle->startReview(
                    financialCalculationId: $this->positiveModelIdentifier(
                        $calculation->getKey(),
                        'Financial calculation identifier',
                    ),
                    reviewedByUserId: $this->positiveModelIdentifier(
                        $actor->getKey(),
                        'Reviewing user identifier',
                    ),
                    reviewedAt: now(),
                    reason: $this->nullableString(
                        $input,
                        'reason',
                    ),
                );

            return $reviewedCalculation->load([
                'priceList:id,public_id',
                'priceListVersion:id,version_number',
                'dailyReport:id,public_id',
                'supersedesCalculation:id,public_id',
                'lines',
            ]);
        } catch (DomainException $exception) {
            throw new ConflictHttpException(
                $exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function approve(
        User $actor,
        string $financialCalculationPublicId,
        array $input,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $calculation = FinancialCalculation::query()
            ->where(
                'public_id',
                $financialCalculationPublicId,
            )
            ->where(
                'organization_id',
                $organizationId,
            )
            ->firstOrFail();

        try {
            $approvedCalculation =
                $this->lifecycle->approve(
                    financialCalculationId: $this->positiveModelIdentifier(
                        $calculation->getKey(),
                        'Financial calculation identifier',
                    ),
                    approvedByUserId: $this->positiveModelIdentifier(
                        $actor->getKey(),
                        'Approving user identifier',
                    ),
                    approvedAt: now(),
                    reason: $this->nullableString(
                        $input,
                        'reason',
                    ),
                );

            return $approvedCalculation->load([
                'priceList:id,public_id',
                'priceListVersion:id,version_number',
                'dailyReport:id,public_id',
                'supersedesCalculation:id,public_id',
                'lines',
            ]);
        } catch (DomainException $exception) {
            throw new ConflictHttpException(
                $exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function close(
        User $actor,
        string $financialCalculationPublicId,
        array $input,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $calculation = FinancialCalculation::query()
            ->where(
                'public_id',
                $financialCalculationPublicId,
            )
            ->where(
                'organization_id',
                $organizationId,
            )
            ->firstOrFail();

        try {
            $closedCalculation =
                $this->lifecycle->close(
                    financialCalculationId: $this->positiveModelIdentifier(
                        $calculation->getKey(),
                        'Financial calculation identifier',
                    ),
                    closedByUserId: $this->positiveModelIdentifier(
                        $actor->getKey(),
                        'Closing user identifier',
                    ),
                    closedAt: now(),
                    reason: $this->nullableString(
                        $input,
                        'reason',
                    ),
                );

            return $closedCalculation->load([
                'priceList:id,public_id',
                'priceListVersion:id,version_number',
                'dailyReport:id,public_id',
                'supersedesCalculation:id,public_id',
                'lines',
            ]);
        } catch (DomainException $exception) {
            throw new ConflictHttpException(
                $exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function requiredString(
        array $input,
        string $key,
    ): string {
        $value = $input[$key] ?? null;

        if (
            ! is_string($value)
            || $value === ''
        ) {
            throw new LogicException(
                sprintf(
                    'Validated input [%s] is unavailable.',
                    $key,
                ),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function requiredPositiveInteger(
        array $input,
        string $key,
    ): int {
        $value = $input[$key] ?? null;

        if (! is_int($value) || $value < 1) {
            throw new LogicException(
                sprintf(
                    'Validated input [%s] must be a positive integer.',
                    $key,
                ),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function nullableString(
        array $input,
        string $key,
    ): ?string {
        $value = $input[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                sprintf(
                    'Validated input [%s] must be a string or null.',
                    $key,
                ),
            );
        }

        return $value;
    }

    private function positiveModelIdentifier(
        mixed $value,
        string $label,
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match(
                    '/^[1-9][0-9]*$/D',
                    $value,
                ) === 1
            )
        ) {
            throw new LogicException(
                $label.' is unavailable.',
            );
        }

        $identifier = (int) $value;

        if ($identifier < 1) {
            throw new LogicException(
                $label.' must be positive.',
            );
        }

        return $identifier;
    }
}
