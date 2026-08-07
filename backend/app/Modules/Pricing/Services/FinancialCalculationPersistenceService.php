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

    public function recalculateApprovedCalculation(
        int $sourceFinancialCalculationId,
        int $dailyReportVersionId,
        int $calculatedByUserId,
        DateTimeInterface $calculatedAt,
        string $reason,
    ): FinancialCalculation {
        $organizationId =
            $this->organizationContext->requireId();

        $this->assertPositiveIdentifier(
            $sourceFinancialCalculationId,
            'Source financial calculation identifier',
        );

        $this->assertPositiveIdentifier(
            $dailyReportVersionId,
            'Daily-report version identifier',
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
                'Recalculation reason',
            );

        if ($normalizedReason === null) {
            throw new InvalidArgumentException(
                'Recalculation reason is required.',
            );
        }

        return DB::transaction(
            function () use (
                $organizationId,
                $sourceFinancialCalculationId,
                $dailyReportVersionId,
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

                $sourceCalculation =
                    FinancialCalculation::query()
                        ->whereKey(
                            $sourceFinancialCalculationId,
                        )
                        ->where(
                            'organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $sourceCalculation
                        instanceof FinancialCalculation
                ) {
                    throw new DomainException(
                        'The source financial calculation does not exist.',
                    );
                }

                if (! $sourceCalculation->isApproved()) {
                    throw new DomainException(
                        'Only an approved financial calculation can be recalculated.',
                    );
                }

                $alreadySuperseded =
                    FinancialCalculation::query()
                        ->where(
                            'supersedes_calculation_id',
                            $sourceFinancialCalculationId,
                        )
                        ->lockForUpdate()
                        ->exists();

                if ($alreadySuperseded) {
                    throw new DomainException(
                        'The source financial calculation has already been superseded.',
                    );
                }

                $sourceCalculationVersion =
                    $this->positiveInteger(
                        $sourceCalculation->getAttribute(
                            'calculation_version',
                        ),
                        'Source calculation version',
                    );

                $sourceDailyReportId =
                    $this->positiveInteger(
                        $sourceCalculation->getAttribute(
                            'daily_report_id',
                        ),
                        'Source daily-report identifier',
                    );

                $sourceDailyReportVersion =
                    $this->positiveInteger(
                        $sourceCalculation->getAttribute(
                            'daily_report_version',
                        ),
                        'Source daily-report version',
                    );

                $sourcePriceListId =
                    $this->positiveInteger(
                        $sourceCalculation->getAttribute(
                            'price_list_id',
                        ),
                        'Source price-list identifier',
                    );

                $sourceRelationshipId =
                    $this->positiveInteger(
                        $sourceCalculation->getAttribute(
                            'organization_relationship_id',
                        ),
                        'Source commercial relationship identifier',
                    );

                $sourceSnapshot =
                    $sourceCalculation->getAttribute(
                        'input_snapshot',
                    );

                if (! is_array($sourceSnapshot)) {
                    throw new LogicException(
                        'The source financial calculation does not contain a valid input snapshot.',
                    );
                }

                $sourceSubtotal =
                    $this->decimalAmount(
                        $sourceCalculation->getAttribute(
                            'subtotal_amount',
                        ),
                        'Source subtotal amount',
                    );

                $sourceTotal =
                    $this->decimalAmount(
                        $sourceCalculation->getAttribute(
                            'total_amount',
                        ),
                        'Source total amount',
                    );

                $dailyReportVersion =
                    DailyReportVersion::query()
                        ->whereKey($dailyReportVersionId)
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $dailyReportVersion
                        instanceof DailyReportVersion
                ) {
                    throw new DomainException(
                        'The amended daily-report version does not exist.',
                    );
                }

                $dailyReportId =
                    $this->positiveInteger(
                        $dailyReportVersion->getAttribute(
                            'daily_report_id',
                        ),
                        'Amended daily-report identifier',
                    );

                if (
                    $dailyReportId !==
                    $sourceDailyReportId
                ) {
                    throw new DomainException(
                        'The amended daily-report version does not belong to the source calculation daily report.',
                    );
                }

                $selectedDailyReportVersion =
                    $this->positiveInteger(
                        $dailyReportVersion->getAttribute(
                            'version_number',
                        ),
                        'Amended daily-report version number',
                    );

                if (
                    $selectedDailyReportVersion <=
                    $sourceDailyReportVersion
                ) {
                    throw new DomainException(
                        'Recalculation requires a newer daily-report version than the source calculation.',
                    );
                }

                $dailyReport = DailyReport::query()
                    ->whereKey($dailyReportId)
                    ->lockForUpdate()
                    ->first();

                if (! $dailyReport instanceof DailyReport) {
                    throw new DomainException(
                        'The amended daily report does not exist.',
                    );
                }

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
                                'Recalculation requires the current '.
                                'daily-report version; selected version %d, '.
                                'current version %d.'
                            ),
                            $selectedDailyReportVersion,
                            $currentDailyReportVersion,
                        ),
                    );
                }

                $existingTargetCalculation =
                    FinancialCalculation::query()
                        ->where(
                            'daily_report_id',
                            $dailyReportId,
                        )
                        ->where(
                            'daily_report_version',
                            $selectedDailyReportVersion,
                        )
                        ->lockForUpdate()
                        ->exists();

                if ($existingTargetCalculation) {
                    throw new DomainException(
                        'The amended daily-report version has already been calculated.',
                    );
                }

                $snapshot =
                    $this->snapshotBuilder->build(
                        $dailyReportVersion,
                        $calculatedMoment,
                    );

                if (
                    $snapshot['daily_report_id'] !==
                    $dailyReportId
                ) {
                    throw new LogicException(
                        'The amended immutable snapshot references a different daily report.',
                    );
                }

                $serviceDate = $this->immutableDate(
                    $snapshot['service_date'],
                    'Daily-report service date',
                );

                $sourceOrganizationId =
                    $this->positiveInteger(
                        $snapshot['organization_id'],
                        'Daily-report source organization identifier',
                    );

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
                        'The amended daily-report organization does not match the immutable snapshot.',
                    );
                }

                $priceList = PriceList::query()
                    ->whereKey($sourcePriceListId)
                    ->lockForUpdate()
                    ->first();

                if (! $priceList instanceof PriceList) {
                    throw new DomainException(
                        'The source price list does not exist.',
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
                        'The verified organization is not the provider of the source price list.',
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
                        'The source price-list customer does not match the amended daily-report organization.',
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
                        'The pricing foundation requires the customer organization to own the price list.',
                    );
                }

                $priceListRelationshipId =
                    $this->positiveInteger(
                        $priceList->getAttribute(
                            'organization_relationship_id',
                        ),
                        'Price-list commercial relationship identifier',
                    );

                if (
                    $priceListRelationshipId !==
                    $sourceRelationshipId
                ) {
                    throw new DomainException(
                        'The source calculation commercial relationship does not match its price list.',
                    );
                }

                $relationship =
                    OrganizationRelationship::query()
                        ->whereKey(
                            $sourceRelationshipId,
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $relationship
                        instanceof OrganizationRelationship
                ) {
                    throw new DomainException(
                        'The source commercial relationship does not exist.',
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
                                'The source commercial relationship is not '.
                                'applicable on amended daily-report '.
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
                        'The source commercial relationship direction does not match the price list.',
                    );
                }

                $priceListVersions =
                    PriceListVersion::query()
                        ->where(
                            'price_list_id',
                            $sourcePriceListId,
                        )
                        ->lockForUpdate()
                        ->get();

                $applicablePriceListVersion = null;

                foreach (
                    $priceListVersions as $candidatePriceListVersion
                ) {
                    if (
                        ! $candidatePriceListVersion
                            instanceof PriceListVersion
                    ) {
                        continue;
                    }

                    if (
                        ! $candidatePriceListVersion->isActive()
                        && ! $candidatePriceListVersion->isReplaced()
                        && ! $candidatePriceListVersion->isExpired()
                    ) {
                        continue;
                    }

                    if (
                        ! $candidatePriceListVersion->isApplicableOn(
                            $serviceDate,
                        )
                    ) {
                        continue;
                    }

                    if (
                        $applicablePriceListVersion
                        instanceof PriceListVersion
                    ) {
                        throw new DomainException(
                            sprintf(
                                (
                                    'Multiple applicable price-list versions '.
                                    'exist for amended daily-report '.
                                    'service date [%s].'
                                ),
                                $serviceDate->format('Y-m-d'),
                            ),
                        );
                    }

                    $applicablePriceListVersion =
                        $candidatePriceListVersion;
                }

                if (
                    ! $applicablePriceListVersion
                        instanceof PriceListVersion
                ) {
                    throw new DomainException(
                        sprintf(
                            (
                                'No applicable price-list version exists '.
                                'for amended daily-report service date [%s].'
                            ),
                            $serviceDate->format('Y-m-d'),
                        ),
                    );
                }

                $priceListVersionId =
                    $this->positiveInteger(
                        $applicablePriceListVersion->getKey(),
                        'Applicable price-list version identifier',
                    );

                $priceListVersionNumber =
                    $this->positiveInteger(
                        $applicablePriceListVersion->getAttribute(
                            'version_number',
                        ),
                        'Applicable price-list version number',
                    );

                $pricingItems =
                    $applicablePriceListVersion->items()
                        ->lockForUpdate()
                        ->get();

                $applicablePriceListVersion->setRelation(
                    'priceList',
                    $priceList,
                );

                $applicablePriceListVersion->setRelation(
                    'items',
                    $pricingItems,
                );

                $result =
                    $this->amountCalculator->calculate(
                        $applicablePriceListVersion,
                        $snapshot,
                    );

                $sourceCurrency =
                    (string) $sourceCalculation->getAttribute(
                        'currency',
                    );

                if ($result->currency !== $sourceCurrency) {
                    throw new DomainException(
                        'Recalculation currency must match the source calculation currency.',
                    );
                }

                $newCalculationVersion =
                    $sourceCalculationVersion + 1;

                $calculation =
                    FinancialCalculation::query()->create([
                        'organization_id' => $organizationId,
                        'organization_relationship_id' => $sourceRelationshipId,
                        'price_list_id' => $sourcePriceListId,
                        'price_list_version_id' => $priceListVersionId,
                        'daily_report_id' => $dailyReportId,
                        'daily_report_version' => $selectedDailyReportVersion,
                        'calculation_version' => $newCalculationVersion,
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
                        'supersedes_calculation_id' => $sourceFinancialCalculationId,
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

                $changedInputValues =
                    $this->financialInputChanges(
                        $sourceSnapshot,
                        $result->inputSnapshot,
                    );

                $financialDifference =
                    $this->subtractDecimalAmounts(
                        $result->totalAmount,
                        $sourceTotal,
                    );

                $subtotalDifference =
                    $this->subtractDecimalAmounts(
                        $result->subtotalAmount,
                        $sourceSubtotal,
                    );

                $calculation->events()->create([
                    'organization_id' => $organizationId,
                    'event_type' => FinancialCalculationEvent::TYPE_RECALCULATED,
                    'from_status' => FinancialCalculation::STATUS_APPROVED,
                    'to_status' => FinancialCalculation::STATUS_CALCULATED,
                    'acted_by_user_id' => $calculatedByUserId,
                    'reason' => $normalizedReason,
                    'metadata' => [
                        'source_calculation_public_id' => (string) $sourceCalculation
                            ->getAttribute('public_id'),
                        'source_calculation_version' => $sourceCalculationVersion,
                        'calculation_version' => $newCalculationVersion,
                        'source_daily_report_version' => $sourceDailyReportVersion,
                        'daily_report_version' => $selectedDailyReportVersion,
                        'price_list_version' => $priceListVersionNumber,
                        'changed_input_values' => $changedInputValues,
                        'previous_subtotal_amount' => $sourceSubtotal,
                        'subtotal_amount' => $result->subtotalAmount,
                        'subtotal_difference' => $subtotalDifference,
                        'previous_total_amount' => $sourceTotal,
                        'total_amount' => $result->totalAmount,
                        'financial_difference' => $financialDifference,
                        'line_count' => count($result->lines),
                        'currency' => $result->currency,
                    ],
                    'created_at' => $calculatedMoment,
                ]);

                $calculation->load([
                    'lines',
                    'events',
                    'priceList',
                    'priceListVersion',
                    'dailyReport',
                    'supersedesCalculation',
                ]);

                return $calculation;
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function financialInputChanges(
        array $before,
        array $after,
    ): array {
        $fields = [
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
            'planned_km',
            'actual_km',
            'actual_km_source',
        ];

        $changes = [];

        foreach ($fields as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue = $after[$field] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $changes[$field] = [
                'before' => $beforeValue,
                'after' => $afterValue,
            ];
        }

        return $changes;
    }

    private function decimalAmount(
        mixed $value,
        string $label,
    ): string {
        if (
            ! is_string($value)
            && ! is_int($value)
        ) {
            throw new LogicException(
                $label.' is not available.',
            );
        }

        $normalized = (string) $value;

        if (
            preg_match(
                '/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D',
                $normalized,
            ) !== 1
        ) {
            throw new LogicException(
                $label.' is not a valid decimal amount.',
            );
        }

        if (! str_contains($normalized, '.')) {
            return $normalized.'.00';
        }

        [$whole, $fraction] =
            explode('.', $normalized, 2);

        return $whole.'.'.str_pad(
            $fraction,
            2,
            '0',
        );
    }

    private function subtractDecimalAmounts(
        string $amount,
        string $baseline,
    ): string {
        $amountMinor =
            $this->decimalAmountToMinorUnits(
                $amount,
            );

        $baselineMinor =
            $this->decimalAmountToMinorUnits(
                $baseline,
            );

        $difference =
            $amountMinor - $baselineMinor;

        $sign = $difference < 0
            ? '-'
            : '';

        $absolute = abs($difference);

        return sprintf(
            '%s%d.%02d',
            $sign,
            intdiv($absolute, 100),
            $absolute % 100,
        );
    }

    private function decimalAmountToMinorUnits(
        string $amount,
    ): int {
        $normalized =
            $this->decimalAmount(
                $amount,
                'Financial amount',
            );

        [$whole, $fraction] =
            explode('.', $normalized, 2);

        return ((int) $whole * 100)
            + (int) $fraction;
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
