<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;

final class FinancialCalculationPersistenceService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly FinancialCalculationSnapshotBuilder $snapshotBuilder,
        private readonly PricingAmountCalculator $amountCalculator,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function createInitialCalculation(
        int $dailyReportVersionId,
        int $priceListVersionId,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
        ?string $reason = null,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $dailyReportVersionId,
            'Daily-report version identifier',
        );

        $this->assertPositiveIdentifier(
            $priceListVersionId,
            'Price-list version identifier',
        );

        $this->assertPositiveIdentifier(
            $calculatedByUserId,
            'Calculating user identifier',
        );

        $calculatedMoment =
            CarbonImmutable::instance($calculatedAt);

        $normalizedReason =
            $this->normalizeNullableText(
                $reason,
                'Calculation reason',
            );

        return DB::transaction(
            function () use (
                $organizationId,
                $dailyReportVersionId,
                $priceListVersionId,
                $calculatedByUserId,
                $calculatedMoment,
                $normalizedReason,
            ): FinancialCalculation {
                $this->assertActiveOrganization(
                    $organizationId,
                );

                $this->assertActiveUserMembership(
                    $calculatedByUserId,
                    $organizationId,
                );

                $this->assertOrganizationPermission(
                    $calculatedByUserId,
                    $organizationId,
                    'compensation.manage',
                );

                $dailyReportVersion =
                    DailyReportVersion::query()
                        ->whereKey($dailyReportVersionId)
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $dailyReportVersion instanceof DailyReportVersion
                ) {
                    throw new DomainException(
                        'The daily-report version does not exist.',
                    );
                }

                $dailyReportId = $this->positiveInteger(
                    $dailyReportVersion->getAttribute(
                        'daily_report_id',
                    ),
                    'Daily-report identifier',
                );

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->lockForUpdate()
                    ->first();

                if (! $dailyReport instanceof DailyReport) {
                    throw new DomainException(
                        'The source daily report does not exist.',
                    );
                }

                $selectedDailyReportVersion =
                    $this->positiveInteger(
                        $dailyReportVersion->getAttribute(
                            'version_number',
                        ),
                        'Selected daily-report version number',
                    );

                $currentDailyReportVersion =
                    $this->positiveInteger(
                        $dailyReport->getAttribute(
                            'current_version',
                        ),
                        'Current daily-report version number',
                    );

                if (
                    $selectedDailyReportVersion !==
                    $currentDailyReportVersion
                ) {
                    throw new DomainException(
                        sprintf(
                            (
                                'Only the current daily-report version '.
                                'can create an initial financial calculation; '.
                                'selected version %d, current version %d.'
                            ),
                            $selectedDailyReportVersion,
                            $currentDailyReportVersion,
                        ),
                    );
                }

                $snapshot = $this->snapshotBuilder->build(
                    $dailyReportVersion,
                    $calculatedMoment,
                );

                if (
                    $snapshot['daily_report_id'] !==
                    $dailyReportId
                ) {
                    throw new LogicException(
                        (
                            'The immutable snapshot references '.
                            'a different daily report.'
                        ),
                    );
                }

                $serviceDate = $this->immutableDate(
                    $snapshot['service_date'],
                    'Daily-report service date',
                );

                $sourceOrganizationId =
                    $snapshot['organization_id'];

                $dailyReportOrganizationId =
                    $this->positiveInteger(
                        $dailyReport->getAttribute(
                            'organization_id',
                        ),
                        'Daily-report organization identifier',
                    );

                if (
                    $dailyReportOrganizationId !==
                    $sourceOrganizationId
                ) {
                    throw new DomainException(
                        (
                            'The daily-report organization does not '.
                            'match the immutable snapshot.'
                        ),
                    );
                }

                $priceListVersion =
                    PriceListVersion::query()
                        ->whereKey($priceListVersionId)
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $priceListVersion instanceof PriceListVersion
                ) {
                    throw new DomainException(
                        'The price-list version does not exist.',
                    );
                }

                if (
                    ! $priceListVersion->isActive()
                    && ! $priceListVersion->isReplaced()
                    && ! $priceListVersion->isExpired()
                ) {
                    throw new DomainException(
                        (
                            'Only an active, replaced or expired '.
                            'price-list version can create a financial calculation.'
                        ),
                    );
                }

                if (
                    ! $priceListVersion->isApplicableOn(
                        $serviceDate,
                    )
                ) {
                    throw new DomainException(
                        sprintf(
                            (
                                'The selected price-list version is not '.
                                'applicable on daily-report service date [%s].'
                            ),
                            $serviceDate->format('Y-m-d'),
                        ),
                    );
                }

                $priceListId = $this->positiveInteger(
                    $priceListVersion->getAttribute(
                        'price_list_id',
                    ),
                    'Price-list identifier',
                );

                $priceList = PriceList::query()
                    ->whereKey($priceListId)
                    ->lockForUpdate()
                    ->first();

                if (! $priceList instanceof PriceList) {
                    throw new DomainException(
                        'The parent price list does not exist.',
                    );
                }

                if (! $priceList->isActive()) {
                    throw new DomainException(
                        (
                            'Only an active price list can create '.
                            'a financial calculation.'
                        ),
                    );
                }

                $providerOrganizationId =
                    $this->positiveInteger(
                        $priceList->getAttribute(
                            'provider_organization_id',
                        ),
                        'Provider organization identifier',
                    );

                if (
                    $providerOrganizationId !==
                    $organizationId
                ) {
                    throw new DomainException(
                        (
                            'The verified organization is not the '.
                            'provider of the selected price list.'
                        ),
                    );
                }

                $customerOrganizationId =
                    $this->positiveInteger(
                        $priceList->getAttribute(
                            'customer_organization_id',
                        ),
                        'Customer organization identifier',
                    );

                if (
                    $customerOrganizationId !==
                    $sourceOrganizationId
                ) {
                    throw new DomainException(
                        (
                            'The selected price-list customer does not '.
                            'match the source daily-report organization.'
                        ),
                    );
                }

                $ownerOrganizationId =
                    $this->positiveInteger(
                        $priceList->getAttribute(
                            'owner_organization_id',
                        ),
                        'Price-list owner organization identifier',
                    );

                if (
                    $ownerOrganizationId !==
                    $customerOrganizationId
                ) {
                    throw new DomainException(
                        (
                            'The initial pricing foundation requires '.
                            'the customer organization to own the price list.'
                        ),
                    );
                }

                $relationshipId =
                    $this->positiveInteger(
                        $priceList->getAttribute(
                            'organization_relationship_id',
                        ),
                        'Commercial relationship identifier',
                    );

                $relationship =
                    OrganizationRelationship::query()
                        ->whereKey($relationshipId)
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $relationship instanceof OrganizationRelationship
                ) {
                    throw new DomainException(
                        (
                            'The selected commercial relationship '.
                            'does not exist.'
                        ),
                    );
                }

                if (
                    $relationship->getAttribute('status') !==
                    OrganizationRelationship::STATUS_ACTIVE
                ) {
                    throw new DomainException(
                        (
                            'The selected commercial relationship '.
                            'is not active.'
                        ),
                    );
                }

                if (
                    ! $relationship->isActiveAt(
                        Carbon::instance($serviceDate),
                    )
                ) {
                    throw new DomainException(
                        sprintf(
                            (
                                'The selected commercial relationship '.
                                'is not applicable on daily-report '.
                                'service date [%s].'
                            ),
                            $serviceDate->format('Y-m-d'),
                        ),
                    );
                }

                $relationshipSourceId =
                    $this->positiveInteger(
                        $relationship->getAttribute(
                            'source_organization_id',
                        ),
                        'Relationship source organization identifier',
                    );

                $relationshipTargetId =
                    $this->positiveInteger(
                        $relationship->getAttribute(
                            'target_organization_id',
                        ),
                        'Relationship target organization identifier',
                    );

                if (
                    $relationshipSourceId !==
                        $customerOrganizationId
                    || $relationshipTargetId !==
                        $providerOrganizationId
                ) {
                    throw new DomainException(
                        (
                            'The commercial relationship direction '.
                            'does not match the selected price list.'
                        ),
                    );
                }

                $existingCalculation =
                    FinancialCalculation::query()
                        ->where(
                            'daily_report_id',
                            $dailyReportId,
                        )
                        ->where(
                            'daily_report_version',
                            $snapshot[
                                'daily_report_version'
                            ],
                        )
                        ->lockForUpdate()
                        ->exists();

                if ($existingCalculation) {
                    throw new DomainException(
                        (
                            'The daily-report version has already '.
                            'been calculated.'
                        ),
                    );
                }

                $pricingItems =
                    $priceListVersion->items()
                        ->lockForUpdate()
                        ->get();

                $priceListVersion->setRelation(
                    'priceList',
                    $priceList,
                );

                $priceListVersion->setRelation(
                    'items',
                    $pricingItems,
                );

                $result = $this->amountCalculator->calculate(
                    $priceListVersion,
                    $snapshot,
                );

                $calculation =
                    FinancialCalculation::query()->create([
                        'organization_id' => $organizationId,

                        'organization_relationship_id' => $relationshipId,

                        'price_list_id' => $priceListId,

                        'price_list_version_id' => $priceListVersionId,

                        'daily_report_id' => $dailyReportId,

                        'daily_report_version' => $snapshot[
                                'daily_report_version'
                            ],

                        'calculation_version' => 1,

                        'status' => FinancialCalculation::STATUS_CALCULATED,

                        'currency' => $result->currency,

                        'input_snapshot' => $result->inputSnapshot,

                        'subtotal_amount' => $result->subtotalAmount,

                        'total_amount' => $result->totalAmount,

                        'calculated_by_user_id' => $calculatedByUserId,

                        'calculated_at' => $calculatedMoment,

                        'approved_by_user_id' => null,
                        'approved_at' => null,
                        'closed_at' => null,
                        'supersedes_calculation_id' => null,
                    ]);

                foreach ($result->lines as $line) {
                    $calculation->lines()->create([
                        'price_list_item_id' => $line->priceListItemId,

                        'pricing_code' => $line->pricingCode,

                        'description' => $line->description,

                        'quantity' => $line->quantity,

                        'unit' => $line->unit,

                        'unit_rate' => $line->unitRate,

                        'currency' => $line->currency,

                        'line_amount' => $line->lineAmount,

                        'source_field' => $line->sourceField,

                        'rounding_scale' => $line->roundingScale,

                        'rounding_method' => $line->roundingMethod,

                        'position' => $line->position,

                        'created_at' => $calculatedMoment,
                    ]);
                }

                $calculation->events()->create([
                    'organization_id' => $organizationId,

                    'event_type' => FinancialCalculationEvent::TYPE_CALCULATED,

                    'from_status' => null,

                    'to_status' => FinancialCalculation::STATUS_CALCULATED,

                    'acted_by_user_id' => $calculatedByUserId,

                    'reason' => $normalizedReason,

                    'metadata' => [
                        'daily_report_id' => $dailyReportId,

                        'daily_report_version' => $snapshot[
                                'daily_report_version'
                            ],

                        'price_list_id' => $priceListId,

                        'price_list_version_id' => $priceListVersionId,

                        'calculation_version' => 1,

                        'line_count' => count($result->lines),

                        'currency' => $result->currency,

                        'subtotal_amount' => $result->subtotalAmount,

                        'total_amount' => $result->totalAmount,
                    ],

                    'created_at' => $calculatedMoment,
                ]);

                $calculation->load([
                    'lines',
                    'events',
                    'priceList',
                    'priceListVersion',
                    'dailyReport',
                ]);

                return $calculation;
            },
            3,
        );
    }

    private function assertPositiveIdentifier(
        int $identifier,
        string $label,
    ): void {
        if ($identifier < 1) {
            throw new InvalidArgumentException(
                $label.' must be a positive integer.',
            );
        }
    }

    private function immutableDate(
        string $value,
        string $label,
    ): CarbonImmutable {
        try {
            $date = CarbonImmutable::parse(
                $value,
                'UTC',
            )->startOfDay();
        } catch (\Throwable $exception) {
            throw new LogicException(
                $label.' is not a valid ISO date.',
                0,
                $exception,
            );
        }

        if ($date->format('Y-m-d') !== $value) {
            throw new LogicException(
                $label.' is not a valid ISO date.',
            );
        }

        return $date;
    }

    private function positiveInteger(
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
                $label.' is not available.',
            );
        }

        $integer = (int) $value;

        if ($integer < 1) {
            throw new LogicException(
                $label.' must be positive.',
            );
        }

        return $integer;
    }

    private function normalizeNullableText(
        ?string $value,
        string $label,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > 2000) {
            throw new InvalidArgumentException(
                $label.' must not exceed 2000 characters.',
            );
        }

        return $normalized;
    }

    private function assertActiveOrganization(
        int $organizationId,
    ): void {
        $exists = Organization::query()
            ->whereKey($organizationId)
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->exists();

        if (! $exists) {
            throw new DomainException(
                'The verified organization is not active.',
            );
        }
    }

    private function assertActiveUserMembership(
        int $userId,
        int $organizationId,
    ): void {
        $activeUserExists = User::query()
            ->whereKey($userId)
            ->where(
                'status',
                User::STATUS_ACTIVE,
            )
            ->exists();

        if (! $activeUserExists) {
            throw new DomainException(
                'The calculating user is not active.',
            );
        }

        $memberships =
            OrganizationMembership::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'user_id',
                    $userId,
                )
                ->where(
                    'status',
                    OrganizationMembership::STATUS_ACTIVE,
                )
                ->get();

        $moment = now();

        $membershipExists = $memberships->contains(
            static fn (
                OrganizationMembership $membership,
            ): bool => $membership->isActiveAt($moment),
        );

        if (! $membershipExists) {
            throw new DomainException(
                (
                    'The calculating user does not have an active '.
                    'membership in the verified organization.'
                ),
            );
        }
    }

    private function assertOrganizationPermission(
        int $userId,
        int $organizationId,
        string $permission,
    ): void {
        $previousOrganizationId =
            $this->permissionRegistrar
                ->getPermissionsTeamId();

        try {
            $this->permissionRegistrar
                ->setPermissionsTeamId(
                    $organizationId,
                );

            $user = User::query()->find($userId);

            if (! $user instanceof User) {
                throw new DomainException(
                    'The calculating user does not exist.',
                );
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            try {
                $hasPermission =
                    $user->hasPermissionTo(
                        $permission,
                    );
            } catch (PermissionDoesNotExist) {
                $hasPermission = false;
            }

            if (! $hasPermission) {
                throw new DomainException(
                    sprintf(
                        (
                            'The calculating user does not have '.
                            'the required organization permission: %s.'
                        ),
                        $permission,
                    ),
                );
            }
        } finally {
            $this->permissionRegistrar
                ->setPermissionsTeamId(
                    $previousOrganizationId,
                );
        }
    }
}
