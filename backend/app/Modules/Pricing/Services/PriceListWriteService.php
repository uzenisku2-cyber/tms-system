<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PriceListWriteService
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly ConditionalPriceListRulePayload $conditionalRulePayload,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function createDraft(
        User $actor,
        array $input,
    ): PriceList {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        $relationshipId = $this->positiveInteger(
            $input,
            'organization_relationship_id',
        );

        $name = $this->requiredString(
            $input,
            'name',
        );

        $description = $this->nullableString(
            $input,
            'description',
        );

        $currency = $this->requiredString(
            $input,
            'currency',
        );

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $relationshipId,
                $name,
                $description,
                $currency,
                $validFrom,
                $validUntil,
                $changeReason,
            ): PriceList {
                $relationship =
                    OrganizationRelationship::query()
                        ->with([
                            'sourceOrganization',
                            'targetOrganization',
                        ])
                        ->whereKey($relationshipId)
                        ->where(
                            'source_organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $relationship->getAttribute(
                        'relationship_type',
                    ) !==
                    OrganizationRelationship::TYPE_SUBCONTRACTING
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not support '
                            .'price-list management.'
                        ),
                    );
                }

                if (! $relationship->isActiveAt(now())) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected commercial relationship is not '
                            .'currently active.'
                        ),
                    );
                }

                $customer =
                    $relationship->sourceOrganization;

                $provider =
                    $relationship->targetOrganization;

                if (
                    ! $customer instanceof Organization
                    || ! $provider instanceof Organization
                ) {
                    throw new LogicException(
                        (
                            'The selected commercial relationship has '
                            .'incomplete organization data.'
                        ),
                    );
                }

                if (
                    ! $customer->isActive()
                    || ! $provider->isActive()
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'Both organizations in the selected relationship '
                            .'must be active.'
                        ),
                    );
                }

                $customerId =
                    (int) $customer->getKey();

                $providerId =
                    (int) $provider->getKey();

                if (
                    $customerId < 1
                    || $providerId < 1
                    || $customerId === $providerId
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not contain '
                            .'two distinct organizations.'
                        ),
                    );
                }

                if ($customerId !== $organizationId) {
                    throw new LogicException(
                        (
                            'The verified organization does not match '
                            .'the relationship customer.'
                        ),
                    );
                }

                $priceList = PriceList::query()->create([
                    'organization_relationship_id' => $relationship->getKey(),

                    'owner_organization_id' => $customerId,

                    'customer_organization_id' => $customerId,

                    'provider_organization_id' => $providerId,

                    'managed_by_organization_id' => $customerId,

                    'name' => $name,

                    'description' => $description,

                    'currency' => $currency,

                    'status' => PriceList::STATUS_DRAFT,

                    'current_version' => 1,

                    'created_by_user_id' => $actorId,
                ]);

                $priceList->versions()->create([
                    'version_number' => 1,

                    'lock_version' => 1,

                    'status' => PriceListVersion::STATUS_DRAFT,

                    'valid_from' => $validFrom,

                    'valid_until' => $validUntil,

                    'change_reason' => $changeReason,

                    'created_by_user_id' => $actorId,
                ]);

                return $priceList->refresh();
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createProviderManagedDraft(
        User $actor,
        int $relationshipId,
        array $input,
    ): PriceList {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($relationshipId < 1) {
            throw new LogicException(
                'The commercial relationship identifier must be positive.',
            );
        }

        $name = $this->requiredString(
            $input,
            'name',
        );

        $description = $this->nullableString(
            $input,
            'description',
        );

        $currency = $this->requiredString(
            $input,
            'currency',
        );

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );
        $items =
            $this->pricingItems($input);

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($input);

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $relationshipId,
                $name,
                $description,
                $currency,
                $validFrom,
                $validUntil,
                $changeReason,
                $items,
                $conditionalRules,
            ): PriceList {
                $relationship =
                    OrganizationRelationship::query()
                        ->with([
                            'sourceOrganization',
                            'targetOrganization',
                        ])
                        ->whereKey($relationshipId)
                        ->where(
                            'target_organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $relationship->getAttribute(
                        'relationship_type',
                    ) !==
                    OrganizationRelationship::TYPE_SUBCONTRACTING
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not support '
                            .'provider-managed billing price lists.'
                        ),
                    );
                }

                if (! $relationship->isActiveAt(now())) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected customer relationship is not '
                            .'currently active.'
                        ),
                    );
                }

                $customer =
                    $relationship->sourceOrganization;

                $provider =
                    $relationship->targetOrganization;

                if (
                    ! $customer instanceof Organization
                    || ! $provider instanceof Organization
                ) {
                    throw new LogicException(
                        (
                            'The selected customer relationship has '
                            .'incomplete organization data.'
                        ),
                    );
                }

                if (
                    ! $customer->isActive()
                    || ! $provider->isActive()
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'Both organizations in the customer relationship '
                            .'must be active.'
                        ),
                    );
                }

                $customerId =
                    (int) $customer->getKey();

                $providerId =
                    (int) $provider->getKey();

                if (
                    $customerId < 1
                    || $providerId < 1
                    || $customerId === $providerId
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected customer relationship does not '
                            .'contain two distinct organizations.'
                        ),
                    );
                }

                if ($providerId !== $organizationId) {
                    throw new LogicException(
                        (
                            'The verified organization does not match '
                            .'the relationship provider.'
                        ),
                    );
                }

                $priceList = PriceList::query()->create([
                    'organization_relationship_id' => $relationship->getKey(),

                    'owner_organization_id' => $customerId,

                    'customer_organization_id' => $customerId,

                    'provider_organization_id' => $providerId,

                    'managed_by_organization_id' => $providerId,

                    'name' => $name,
                    'description' => $description,
                    'currency' => $currency,
                    'status' => PriceList::STATUS_DRAFT,
                    'current_version' => 1,
                    'created_by_user_id' => $actorId,
                ]);

                $version = $priceList->versions()->create([
                    'version_number' => 1,
                    'lock_version' => 1,
                    'status' => PriceListVersion::STATUS_DRAFT,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'change_reason' => $changeReason,
                    'created_by_user_id' => $actorId,
                ]);

                foreach ($items as $item) {
                    $code = $item['code'];

                    $version->items()->create([
                        'code' => $code,
                        'description' => $item['description'],
                        'calculation_method' => (
                            PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                        ),
                        'unit' => $this->unitForCode($code),
                        'unit_rate' => $item['unit_rate'],
                        'currency' => $currency,
                        'quantity_source' => $code,
                        'rounding_scale' => 2,
                        'rounding_method' => (
                            PriceListItem::ROUNDING_METHOD_HALF_UP
                        ),
                        'position' => $this->positionForCode($code),
                    ]);
                }

                $this->persistConditionalRules(
                    $version,
                    $conditionalRules,
                );

                return $priceList->refresh();
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createCustomerManagedDraft(
        User $actor,
        int $relationshipId,
        array $input,
    ): PriceList {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($relationshipId < 1) {
            throw new LogicException(
                'The commercial relationship identifier must be positive.',
            );
        }

        $name = $this->requiredString(
            $input,
            'name',
        );

        $description = $this->nullableString(
            $input,
            'description',
        );

        $currency = $this->requiredString(
            $input,
            'currency',
        );

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );
        $items =
            $this->pricingItems($input);

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($input);

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $relationshipId,
                $name,
                $description,
                $currency,
                $validFrom,
                $validUntil,
                $changeReason,
                $items,
                $conditionalRules,
            ): PriceList {
                $relationship =
                    OrganizationRelationship::query()
                        ->with([
                            'sourceOrganization',
                            'targetOrganization',
                        ])
                        ->whereKey($relationshipId)
                        ->where(
                            'source_organization_id',
                            $organizationId,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $relationship->getAttribute(
                        'relationship_type',
                    ) !==
                    OrganizationRelationship::TYPE_SUBCONTRACTING
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected relationship does not support '
                            .'customer-managed external-carrier price lists.'
                        ),
                    );
                }

                if (! $relationship->isActiveAt(now())) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected external-carrier relationship is not '
                            .'currently active.'
                        ),
                    );
                }

                $customer =
                    $relationship->sourceOrganization;

                $provider =
                    $relationship->targetOrganization;

                if (
                    ! $customer instanceof Organization
                    || ! $provider instanceof Organization
                ) {
                    throw new LogicException(
                        (
                            'The selected external-carrier relationship has '
                            .'incomplete organization data.'
                        ),
                    );
                }

                if (
                    ! $customer->isActive()
                    || ! $provider->isActive()
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'Both organizations in the external-carrier relationship '
                            .'must be active.'
                        ),
                    );
                }

                $customerId =
                    (int) $customer->getKey();

                $providerId =
                    (int) $provider->getKey();

                if (
                    $customerId < 1
                    || $providerId < 1
                    || $customerId === $providerId
                ) {
                    $this->validationFailure(
                        'organization_relationship_id',
                        (
                            'The selected external-carrier relationship does not '
                            .'contain two distinct organizations.'
                        ),
                    );
                }

                if ($customerId !== $organizationId) {
                    throw new LogicException(
                        (
                            'The verified organization does not match '
                            .'the relationship customer.'
                        ),
                    );
                }

                $priceList = PriceList::query()->create([
                    'organization_relationship_id' => $relationship->getKey(),

                    'owner_organization_id' => $customerId,

                    'customer_organization_id' => $customerId,

                    'provider_organization_id' => $providerId,

                    'managed_by_organization_id' => $customerId,

                    'name' => $name,
                    'description' => $description,
                    'currency' => $currency,
                    'status' => PriceList::STATUS_DRAFT,
                    'current_version' => 1,
                    'created_by_user_id' => $actorId,
                ]);

                $version = $priceList->versions()->create([
                    'version_number' => 1,
                    'lock_version' => 1,
                    'status' => PriceListVersion::STATUS_DRAFT,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'change_reason' => $changeReason,
                    'created_by_user_id' => $actorId,
                ]);

                foreach ($items as $item) {
                    $code = $item['code'];

                    $version->items()->create([
                        'code' => $code,
                        'description' => $item['description'],
                        'calculation_method' => (
                            PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                        ),
                        'unit' => $this->unitForCode($code),
                        'unit_rate' => $item['unit_rate'],
                        'currency' => $currency,
                        'quantity_source' => $code,
                        'rounding_scale' => 2,
                        'rounding_method' => (
                            PriceListItem::ROUNDING_METHOD_HALF_UP
                        ),
                        'position' => $this->positionForCode($code),
                    ]);
                }

                $this->persistConditionalRules(
                    $version,
                    $conditionalRules,
                );

                return $priceList->refresh();
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createCustomerManagedDraftVersion(
        User $actor,
        int $relationshipId,
        string $publicId,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $relationshipId,
                $publicId,
                $input,
            ): PriceListVersion {
                $priceList = $this->lockCustomerManagedPriceList(
                    $organizationId,
                    $relationshipId,
                    $publicId,
                );

                $currency = $this->requiredString(
                    $input,
                    'currency',
                );

                if (
                    $currency !==
                    (string) $priceList->getAttribute('currency')
                ) {
                    $this->validationFailure(
                        'currency',
                        'A new version must keep the price-list currency.',
                    );
                }

                $draft = $this->createDraftVersion(
                    $actor,
                    $publicId,
                    $input,
                );

                $replacement = $input;
                $replacement['expected_lock_version'] =
                    (int) $draft->getAttribute('lock_version');

                unset(
                    $replacement['expected_current_version'],
                    $replacement['currency'],
                );

                return $this->updateDraftVersion(
                    $actor,
                    $publicId,
                    (int) $draft->getAttribute('version_number'),
                    $replacement,
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateCustomerManagedDraftVersion(
        User $actor,
        int $relationshipId,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $relationshipId,
                $publicId,
                $versionNumber,
                $input,
            ): PriceListVersion {
                $this->lockCustomerManagedPriceList(
                    $organizationId,
                    $relationshipId,
                    $publicId,
                );

                return $this->updateDraftVersion(
                    $actor,
                    $publicId,
                    $versionNumber,
                    $input,
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function approveCustomerManagedDraftVersion(
        User $actor,
        int $relationshipId,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $relationshipId,
                $publicId,
                $versionNumber,
                $input,
            ): PriceListVersion {
                $this->lockCustomerManagedPriceList(
                    $organizationId,
                    $relationshipId,
                    $publicId,
                );

                return $this->approveDraftVersion(
                    $actor,
                    $publicId,
                    $versionNumber,
                    $input,
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function activateCustomerManagedApprovedVersion(
        User $actor,
        int $relationshipId,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $relationshipId,
                $publicId,
                $versionNumber,
                $input,
            ): PriceListVersion {
                $this->lockCustomerManagedPriceList(
                    $organizationId,
                    $relationshipId,
                    $publicId,
                );

                return $this->activateApprovedVersion(
                    $actor,
                    $publicId,
                    $versionNumber,
                    $input,
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function expireCustomerManagedActiveVersion(
        User $actor,
        int $relationshipId,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $relationshipId,
                $publicId,
                $versionNumber,
                $input,
            ): PriceListVersion {
                $this->lockCustomerManagedPriceList(
                    $organizationId,
                    $relationshipId,
                    $publicId,
                );

                return $this->expireActiveVersion(
                    $actor,
                    $publicId,
                    $versionNumber,
                    $input,
                );
            },
            3,
        );
    }

    public function createDraftVersion(
        User $actor,
        string $publicId,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        $expectedCurrentVersion = $this->positiveInteger(
            $input,
            'expected_current_version',
        );

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $publicId,
                $expectedCurrentVersion,
                $validFrom,
                $validUntil,
                $changeReason,
            ): PriceListVersion {
                $priceList = PriceList::query()
                    ->where(
                        function ($managementQuery) use (
                            $organizationId,
                        ): void {
                            $managementQuery
                                ->where(
                                    'managed_by_organization_id',
                                    $organizationId,
                                )
                                ->orWhere(
                                    function ($legacyOwnerQuery) use (
                                        $organizationId,
                                    ): void {
                                        $legacyOwnerQuery
                                            ->whereNull(
                                                'managed_by_organization_id',
                                            )
                                            ->where(
                                                'owner_organization_id',
                                                $organizationId,
                                            );
                                    },
                                );
                        },
                    )
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($priceList->isArchived()) {
                    throw new ConflictHttpException(
                        'Archived price lists cannot receive new versions.',
                    );
                }

                $currentVersion = (int) $priceList->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedCurrentVersion) {
                    throw new ConflictHttpException(
                        'The price list has changed.',
                    );
                }

                $current = $priceList->versions()
                    ->where('version_number', $currentVersion)
                    ->lockForUpdate()
                    ->first();

                if (! $current instanceof PriceListVersion) {
                    throw new LogicException(
                        'The current price-list version is unavailable.',
                    );
                }

                if ($current->isDraft()) {
                    throw new ConflictHttpException(
                        'A draft price-list version already exists.',
                    );
                }

                $nextVersion = $currentVersion + 1;

                $version = $priceList->versions()->create([
                    'version_number' => $nextVersion,
                    'lock_version' => 1,
                    'status' => PriceListVersion::STATUS_DRAFT,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'change_reason' => $changeReason,
                    'created_by_user_id' => $actorId,
                ]);

                $priceList->setAttribute(
                    'current_version',
                    $nextVersion,
                );

                $priceList->saveOrFail();

                $this->copyConditionalRules(
                    $current,
                    $version,
                );

                return $version->fresh(
                    $this->versionRelations(),
                ) ?? throw new LogicException(
                    'The created price-list version could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateDraftVersion(
        User $actor,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        if ($versionNumber < 1) {
            throw new LogicException(
                'The price-list version number must be positive.',
            );
        }

        $expectedLockVersion = $this->positiveInteger(
            $input,
            'expected_lock_version',
        );

        $updateName = array_key_exists(
            'name',
            $input,
        );

        $name = $updateName
            ? $this->requiredString(
                $input,
                'name',
            )
            : null;

        $updateDescription = array_key_exists(
            'description',
            $input,
        );

        $description = $updateDescription
            ? $this->nullableString(
                $input,
                'description',
            )
            : null;

        $validFrom = $this->nullableString(
            $input,
            'valid_from',
        );

        $validUntil = $this->nullableString(
            $input,
            'valid_until',
        );

        $changeReason = $this->nullableString(
            $input,
            'change_reason',
        );

        $items = $this->pricingItems($input);

        $replaceConditionalRules = array_key_exists(
            'conditional_rules',
            $input,
        );

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($input);

        return DB::transaction(
            function () use (
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
                $updateName,
                $name,
                $updateDescription,
                $description,
                $validFrom,
                $validUntil,
                $changeReason,
                $items,
                $replaceConditionalRules,
                $conditionalRules,
            ): PriceListVersion {
                $priceList = PriceList::query()
                    ->where(
                        function ($managementQuery) use (
                            $organizationId,
                        ): void {
                            $managementQuery
                                ->where(
                                    'managed_by_organization_id',
                                    $organizationId,
                                )
                                ->orWhere(
                                    function ($legacyOwnerQuery) use (
                                        $organizationId,
                                    ): void {
                                        $legacyOwnerQuery
                                            ->whereNull(
                                                'managed_by_organization_id',
                                            )
                                            ->where(
                                                'owner_organization_id',
                                                $organizationId,
                                            );
                                    },
                                );
                        },
                    )
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $version = $priceList->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $version->isDraft()) {
                    throw new ConflictHttpException(
                        'Only draft price-list versions may be updated.',
                    );
                }

                $currentLockVersion = (int) $version->getAttribute(
                    'lock_version',
                );

                if ($currentLockVersion !== $expectedLockVersion) {
                    throw new ConflictHttpException(
                        'The price-list draft version has changed.',
                    );
                }

                $metadata = [];

                if ($updateName && is_string($name)) {
                    $metadata['name'] = $name;
                }

                if ($updateDescription) {
                    $metadata['description'] = $description;
                }

                if ($metadata !== []) {
                    $priceList->fill($metadata);
                    $priceList->saveOrFail();
                }

                $currency = $priceList->getAttribute('currency');

                if (
                    ! is_string($currency)
                    || preg_match('/^[A-Z]{3}$/', $currency) !== 1
                ) {
                    throw new LogicException(
                        'The price-list currency is invalid.',
                    );
                }

                $version->fill([
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'change_reason' => $changeReason,
                    'lock_version' => $currentLockVersion + 1,
                ]);

                $version->save();

                $version->items()->delete();

                foreach ($items as $item) {
                    $code = $item['code'];

                    $version->items()->create([
                        'code' => $code,
                        'description' => $item['description'],
                        'calculation_method' => (
                            PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                        ),
                        'unit' => $this->unitForCode($code),
                        'unit_rate' => $item['unit_rate'],
                        'currency' => $currency,
                        'quantity_source' => $code,
                        'rounding_scale' => 2,
                        'rounding_method' => (
                            PriceListItem::ROUNDING_METHOD_HALF_UP
                        ),
                        'position' => $this->positionForCode($code),
                    ]);
                }

                if ($replaceConditionalRules) {
                    $this->replaceConditionalRules(
                        $version,
                        $conditionalRules,
                    );
                }

                return $version->fresh(
                    $this->versionRelations(),
                ) ?? throw new LogicException(
                    'The updated price-list version could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function approveDraftVersion(
        User $actor,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        if ($versionNumber < 1) {
            throw new LogicException(
                'The price-list version number must be positive.',
            );
        }

        $expectedLockVersion = $this->positiveInteger(
            $input,
            'expected_lock_version',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $actorId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
            ): PriceListVersion {
                $priceList = PriceList::query()
                    ->where(
                        function ($managementQuery) use (
                            $organizationId,
                        ): void {
                            $managementQuery
                                ->where(
                                    'managed_by_organization_id',
                                    $organizationId,
                                )
                                ->orWhere(
                                    function ($legacyOwnerQuery) use (
                                        $organizationId,
                                    ): void {
                                        $legacyOwnerQuery
                                            ->whereNull(
                                                'managed_by_organization_id',
                                            )
                                            ->where(
                                                'owner_organization_id',
                                                $organizationId,
                                            );
                                    },
                                );
                        },
                    )
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($priceList->isArchived()) {
                    throw new ConflictHttpException(
                        'Archived price lists cannot approve versions.',
                    );
                }

                $version = $priceList->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentVersion = (int) $priceList->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $versionNumber) {
                    throw new ConflictHttpException(
                        'Only the current price-list version may be approved.',
                    );
                }

                if (! $version->isDraft()) {
                    throw new ConflictHttpException(
                        'Only draft price-list versions may be approved.',
                    );
                }

                $currentLockVersion = (int) $version->getAttribute(
                    'lock_version',
                );

                if ($currentLockVersion !== $expectedLockVersion) {
                    throw new ConflictHttpException(
                        'The price-list draft version has changed.',
                    );
                }

                $this->assertApprovableItems(
                    $priceList,
                    $version,
                );

                $this->assertApprovableConditionalRules(
                    $version,
                );

                $version->fill([
                    'status' => PriceListVersion::STATUS_APPROVED,
                    'approved_by_user_id' => $actorId,
                    'approved_at' => now(),
                    'activated_at' => null,
                ]);

                $version->saveOrFail();

                return $version->fresh(
                    $this->versionRelations(),
                ) ?? throw new LogicException(
                    'The approved price-list version could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function activateApprovedVersion(
        User $actor,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        if ($versionNumber < 1) {
            throw new LogicException(
                'The price-list version number must be positive.',
            );
        }

        $expectedLockVersion = $this->positiveInteger(
            $input,
            'expected_lock_version',
        );

        return DB::transaction(
            function () use (
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
            ): PriceListVersion {
                $priceList = PriceList::query()
                    ->where(
                        function ($managementQuery) use (
                            $organizationId,
                        ): void {
                            $managementQuery
                                ->where(
                                    'managed_by_organization_id',
                                    $organizationId,
                                )
                                ->orWhere(
                                    function ($legacyOwnerQuery) use (
                                        $organizationId,
                                    ): void {
                                        $legacyOwnerQuery
                                            ->whereNull(
                                                'managed_by_organization_id',
                                            )
                                            ->where(
                                                'owner_organization_id',
                                                $organizationId,
                                            );
                                    },
                                );
                        },
                    )
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($priceList->isArchived()) {
                    throw new ConflictHttpException(
                        'Archived price lists cannot activate versions.',
                    );
                }

                $version = $priceList->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentVersion = (int) $priceList->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $versionNumber) {
                    throw new ConflictHttpException(
                        'Only the current price-list version may be activated.',
                    );
                }

                if (! $version->isApproved()) {
                    throw new ConflictHttpException(
                        'Only approved price-list versions may be activated.',
                    );
                }

                $currentLockVersion = (int) $version->getAttribute(
                    'lock_version',
                );

                if ($currentLockVersion !== $expectedLockVersion) {
                    throw new ConflictHttpException(
                        'The price-list approved version has changed.',
                    );
                }

                $approvedByUserId = (int) $version->getAttribute(
                    'approved_by_user_id',
                );

                $approvedAt = $version->getAttribute(
                    'approved_at',
                );

                if (
                    $approvedByUserId < 1
                    || ! $approvedAt instanceof DateTimeInterface
                ) {
                    throw new ConflictHttpException(
                        'Only approved price-list versions may be activated.',
                    );
                }

                $validFrom = $version->getAttribute(
                    'valid_from',
                );

                $validUntil = $version->getAttribute(
                    'valid_until',
                );

                if (
                    ! $validFrom instanceof DateTimeInterface
                    || (
                        $validUntil !== null
                        && (
                            ! $validUntil instanceof DateTimeInterface
                            || $validUntil < $validFrom
                        )
                    )
                ) {
                    throw new ConflictHttpException(
                        'A valid effective period is required before activation.',
                    );
                }

                $activeVersions = $priceList->versions()
                    ->where('status', PriceListVersion::STATUS_ACTIVE)
                    ->where('version_number', '<>', $versionNumber)
                    ->orderBy('version_number')
                    ->lockForUpdate()
                    ->get();

                if ($activeVersions->count() > 1) {
                    throw new ConflictHttpException(
                        'Multiple active price-list versions require manual repair.',
                    );
                }

                $activeVersion = $activeVersions->first();

                if ($activeVersion instanceof PriceListVersion) {
                    $activeValidFrom = $activeVersion->getAttribute(
                        'valid_from',
                    );

                    $activeValidUntil = $activeVersion->getAttribute(
                        'valid_until',
                    );

                    if (
                        ! $activeValidFrom instanceof DateTimeInterface
                        || (
                            $activeValidUntil !== null
                            && ! $activeValidUntil instanceof DateTimeInterface
                        )
                    ) {
                        throw new ConflictHttpException(
                            'The active price-list version has an invalid effective period.',
                        );
                    }

                    $replacementStart = CarbonImmutable::instance(
                        $validFrom,
                    )->startOfDay();

                    $activeStart = CarbonImmutable::instance(
                        $activeValidFrom,
                    )->startOfDay();

                    $replacementBoundary =
                        $replacementStart->subDay();

                    if (
                        $replacementBoundary->isBefore(
                            $activeStart,
                        )
                    ) {
                        throw new ConflictHttpException(
                            'The replacement effective date must follow the active version start date.',
                        );
                    }

                    if (
                        $activeValidUntil === null
                        || ! CarbonImmutable::instance(
                            $activeValidUntil,
                        )->startOfDay()->isBefore(
                            $replacementStart,
                        )
                    ) {
                        $activeVersion->setAttribute(
                            'valid_until',
                            $replacementBoundary,
                        );
                    }

                    $activeVersion->setAttribute(
                        'status',
                        PriceListVersion::STATUS_REPLACED,
                    );

                    $activeVersion->saveOrFail();
                }

                $version->fill([
                    'status' => PriceListVersion::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);

                $version->saveOrFail();

                $priceList->setAttribute(
                    'status',
                    PriceList::STATUS_ACTIVE,
                );

                $priceList->saveOrFail();

                return $version->fresh(
                    $this->versionRelations(),
                ) ?? throw new LogicException(
                    'The activated price-list version could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function expireActiveVersion(
        User $actor,
        string $publicId,
        int $versionNumber,
        array $input,
    ): PriceListVersion {
        $organizationId =
            $this->organizationContext->requireId();

        $actorId = (int) $actor->getKey();

        if ($actorId < 1) {
            throw new LogicException(
                'The authenticated user has no valid identifier.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        if ($versionNumber < 1) {
            throw new LogicException(
                'The price-list version number must be positive.',
            );
        }

        $expectedLockVersion = $this->positiveInteger(
            $input,
            'expected_lock_version',
        );

        $requestedValidUntil = CarbonImmutable::parse(
            $this->requiredString(
                $input,
                'valid_until',
            ),
        )->startOfDay();

        return DB::transaction(
            function () use (
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
                $requestedValidUntil,
            ): PriceListVersion {
                $priceList = PriceList::query()
                    ->where(
                        function ($managementQuery) use (
                            $organizationId,
                        ): void {
                            $managementQuery
                                ->where(
                                    'managed_by_organization_id',
                                    $organizationId,
                                )
                                ->orWhere(
                                    function ($legacyOwnerQuery) use (
                                        $organizationId,
                                    ): void {
                                        $legacyOwnerQuery
                                            ->whereNull(
                                                'managed_by_organization_id',
                                            )
                                            ->where(
                                                'owner_organization_id',
                                                $organizationId,
                                            );
                                    },
                                );
                        },
                    )
                    ->where('public_id', $publicId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($priceList->isArchived()) {
                    throw new ConflictHttpException(
                        'Archived price lists cannot expire versions.',
                    );
                }

                if (! $priceList->isActive()) {
                    throw new ConflictHttpException(
                        'Only active price lists may expire versions.',
                    );
                }

                $version = $priceList->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $version->isActive()) {
                    throw new ConflictHttpException(
                        'Only active price-list versions may be expired.',
                    );
                }

                $currentLockVersion = (int) $version->getAttribute(
                    'lock_version',
                );

                if ($currentLockVersion !== $expectedLockVersion) {
                    throw new ConflictHttpException(
                        'The price-list active version has changed.',
                    );
                }

                $otherActiveVersions = $priceList->versions()
                    ->where(
                        'status',
                        PriceListVersion::STATUS_ACTIVE,
                    )
                    ->where(
                        'version_number',
                        '<>',
                        $versionNumber,
                    )
                    ->lockForUpdate()
                    ->get();

                if ($otherActiveVersions->isNotEmpty()) {
                    throw new ConflictHttpException(
                        'The price-list aggregate contains multiple active versions and requires repair.',
                    );
                }

                $validFrom = $version->getAttribute(
                    'valid_from',
                );

                $storedValidUntil = $version->getAttribute(
                    'valid_until',
                );

                $activatedAt = $version->getAttribute(
                    'activated_at',
                );

                if (
                    ! $validFrom instanceof DateTimeInterface
                    || ! $activatedAt instanceof DateTimeInterface
                    || (
                        $storedValidUntil !== null
                        && ! $storedValidUntil instanceof DateTimeInterface
                    )
                ) {
                    throw new ConflictHttpException(
                        'A valid active effective period is required before expiration.',
                    );
                }

                $validFromDate = CarbonImmutable::instance(
                    $validFrom,
                )->startOfDay();

                if ($requestedValidUntil->isBefore(
                    $validFromDate,
                )) {
                    throw new ConflictHttpException(
                        'The expiration date cannot precede the active version start date.',
                    );
                }

                if ($requestedValidUntil->isAfter(
                    CarbonImmutable::now()->startOfDay(),
                )) {
                    throw new ConflictHttpException(
                        'The expiration date cannot be in the future.',
                    );
                }

                if ($storedValidUntil instanceof DateTimeInterface) {
                    $storedValidUntilDate = CarbonImmutable::instance(
                        $storedValidUntil,
                    )->startOfDay();

                    if ($storedValidUntilDate->isBefore(
                        $validFromDate,
                    )) {
                        throw new ConflictHttpException(
                            'A valid active effective period is required before expiration.',
                        );
                    }

                    if ($requestedValidUntil->isAfter(
                        $storedValidUntilDate,
                    )) {
                        throw new ConflictHttpException(
                            'The expiration date cannot extend the active version effective period.',
                        );
                    }
                }

                $version->fill([
                    'status' => PriceListVersion::STATUS_EXPIRED,
                    'valid_until' => $requestedValidUntil,
                ]);

                $version->saveOrFail();

                return $version->fresh(
                    $this->versionRelations(),
                ) ?? throw new LogicException(
                    'The expired price-list version could not be reloaded.',
                );
            },
            3,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    private function persistConditionalRules(
        PriceListVersion $version,
        array $rules,
    ): void {
        foreach ($rules as $ruleIndex => $payload) {
            $numeratorSources = $payload[
                'metric_numerator_sources'
            ];
            $denominatorSources = $payload[
                'metric_denominator_sources'
            ];

            if (
                ! is_array($numeratorSources)
                || $numeratorSources === []
                || ! is_array($denominatorSources)
            ) {
                throw new LogicException(
                    'Normalized conditional metric components are invalid.',
                );
            }

            $rule = $version->conditionalRules()->create([
                'code' => $payload['code'],
                'name' => $payload['name'],
                'description' => $payload['description'],
                'metric_type' => $payload['metric_type'],
                'metric_numerator_source' => $numeratorSources[0],
                'metric_denominator_source' => $denominatorSources[0] ?? null,
                'evaluation_scope' => $payload['evaluation_scope'],
                'reward_method' => $payload['reward_method'],
                'reward_quantity_source' => $payload['reward_quantity_source'],
                'reward_target_item_code' => $payload['reward_target_item_code'],
                'rounding_scale' => $payload['rounding_scale'],
                'rounding_method' => PriceListConditionalRule::ROUNDING_METHOD_HALF_UP,
                'position' => $ruleIndex + 1,
            ]);

            foreach ($numeratorSources as $index => $source) {
                $rule->metricComponents()->create([
                    'component_role' => PriceListConditionalRuleMetricComponent::ROLE_NUMERATOR,
                    'metric_source' => $source,
                    'position' => $index + 1,
                ]);
            }

            foreach ($denominatorSources as $index => $source) {
                $rule->metricComponents()->create([
                    'component_role' => PriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR,
                    'metric_source' => $source,
                    'position' => $index + 1,
                ]);
            }

            $bands = $payload['bands'];

            if (! is_array($bands) || $bands === []) {
                throw new LogicException(
                    'Normalized conditional pricing bands are invalid.',
                );
            }

            foreach ($bands as $bandIndex => $band) {
                if (! is_array($band)) {
                    throw new LogicException(
                        'A normalized conditional pricing band is invalid.',
                    );
                }

                $rule->bands()->create([
                    'minimum_value' => $band['minimum_value'],
                    'maximum_value' => $band['maximum_value'],
                    'minimum_inclusive' => $band['minimum_inclusive'],
                    'maximum_inclusive' => $band['maximum_inclusive'],
                    'adjustment_value' => $band['adjustment_value'],
                    'position' => $bandIndex + 1,
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    private function replaceConditionalRules(
        PriceListVersion $version,
        array $rules,
    ): void {
        $existingRules = $version->conditionalRules()
            ->lockForUpdate()
            ->get();

        foreach ($existingRules as $rule) {
            $rule->bands()->delete();
            $rule->metricComponents()->delete();
            $rule->delete();
        }

        $this->persistConditionalRules($version, $rules);
    }

    private function lockCustomerManagedPriceList(
        int $organizationId,
        int $relationshipId,
        string $publicId,
    ): PriceList {
        if ($organizationId < 1 || $relationshipId < 1) {
            throw new LogicException(
                'The external-carrier management scope is invalid.',
            );
        }

        if ($publicId === '') {
            throw new LogicException(
                'The price-list public identifier is required.',
            );
        }

        $relationship = OrganizationRelationship::query()
            ->with('targetOrganization')
            ->whereKey($relationshipId)
            ->where(
                'source_organization_id',
                $organizationId,
            )
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->lockForUpdate()
            ->firstOrFail();

        abort_unless(
            $relationship->isActiveAt(now()),
            404,
        );

        $provider = $relationship->targetOrganization;

        abort_unless(
            $provider instanceof Organization
                && $provider->isActive()
                && $provider->getAttribute('type') ===
                    Organization::TYPE_SUBCONTRACTOR,
            404,
        );

        return PriceList::query()
            ->where(
                'organization_relationship_id',
                $relationshipId,
            )
            ->where('public_id', $publicId)
            ->where(
                'owner_organization_id',
                $organizationId,
            )
            ->where(
                'customer_organization_id',
                $organizationId,
            )
            ->where(
                'provider_organization_id',
                (int) $provider->getKey(),
            )
            ->where(
                'managed_by_organization_id',
                $organizationId,
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function copyConditionalRules(
        PriceListVersion $source,
        PriceListVersion $target,
    ): void {

        $rules = $this->conditionalRulePayload->fromInput([
            'conditional_rules' => $this->conditionalRuleInputFromVersion($source),
        ]);

        $this->persistConditionalRules($target, $rules);
    }

    private function assertApprovableConditionalRules(
        PriceListVersion $version,
    ): void {
        try {
            $input = $this->conditionalRuleInputFromVersion(
                $version,
            );

            $this->conditionalRulePayload->fromInput([
                'conditional_rules' => $input,
            ]);
        } catch (ValidationException|LogicException) {
            throw new ConflictHttpException(
                'The conditional pricing configuration is incomplete or invalid.',
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function conditionalRuleInputFromVersion(
        PriceListVersion $version,
    ): array {
        $rules = [];

        foreach (
            $version->conditionalRules()->orderBy('position')->get() as $rule
        ) {
            $components = $rule->metricComponents()
                ->orderBy('position')
                ->get();
            $numeratorSources = [];
            $denominatorSources = [];

            foreach ($components as $component) {
                $source = (string) $component->getAttribute(
                    'metric_source',
                );

                if (
                    $component->getAttribute('component_role') ===
                        PriceListConditionalRuleMetricComponent::ROLE_NUMERATOR
                ) {
                    $numeratorSources[] = $source;

                    continue;
                }

                if (
                    $component->getAttribute('component_role') ===
                        PriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR
                ) {
                    $denominatorSources[] = $source;
                }
            }

            if ($components->isEmpty()) {
                $legacyNumerator = $rule->getAttribute(
                    'metric_numerator_source',
                );
                $legacyDenominator = $rule->getAttribute(
                    'metric_denominator_source',
                );

                if (is_string($legacyNumerator)) {
                    $numeratorSources[] = $legacyNumerator;
                }

                if (is_string($legacyDenominator)) {
                    $denominatorSources[] = $legacyDenominator;
                }
            }

            $bands = [];

            foreach ($rule->bands()->orderBy('position')->get() as $band) {
                $bands[] = [
                    'minimum_value' => $band->getAttribute('minimum_value'),
                    'maximum_value' => $band->getAttribute('maximum_value'),
                    'minimum_inclusive' => (bool) $band->getAttribute(
                        'minimum_inclusive',
                    ),
                    'maximum_inclusive' => (bool) $band->getAttribute(
                        'maximum_inclusive',
                    ),
                    'adjustment_value' => $band->getAttribute('adjustment_value'),
                ];
            }

            $rules[] = [
                'code' => $rule->getAttribute('code'),
                'name' => $rule->getAttribute('name'),
                'description' => $rule->getAttribute('description'),
                'metric_type' => $rule->getAttribute('metric_type'),
                'metric_numerator_sources' => $numeratorSources,
                'metric_denominator_sources' => $denominatorSources,
                'evaluation_scope' => $rule->getAttribute('evaluation_scope'),
                'reward_method' => $rule->getAttribute('reward_method'),
                'reward_quantity_source' => $rule->getAttribute('reward_quantity_source'),
                'reward_target_item_code' => $rule->getAttribute('reward_target_item_code'),
                'rounding_scale' => $rule->getAttribute('rounding_scale'),
                'bands' => $bands,
            ];
        }

        return $rules;
    }

    /** @return list<string> */
    private function versionRelations(): array
    {
        return [
            'items',
            'conditionalRules.metricComponents',
            'conditionalRules.bands',
        ];
    }

    private function assertApprovableItems(
        PriceList $priceList,
        PriceListVersion $version,
    ): void {
        $items = $version->items()
            ->lockForUpdate()
            ->get();

        $currency = $priceList->getAttribute('currency');

        if (
            ! is_string($currency)
            || preg_match('/^[A-Z]{3}$/', $currency) !== 1
            || $items->count() !== count(PriceListItem::CODES)
        ) {
            throw new ConflictHttpException(
                'The complete canonical pricing-item set is required before approval.',
            );
        }

        foreach (PriceListItem::CODES as $index => $code) {
            $item = $items->get($index);
            $unitRate = $item instanceof PriceListItem
                ? $item->getAttribute('unit_rate')
                : null;

            if (
                ! $item instanceof PriceListItem
                || $item->getAttribute('code') !== $code
                || $item->getAttribute('calculation_method') !==
                    PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                || $item->getAttribute('unit') !== $this->unitForCode($code)
                || $item->getAttribute('currency') !== $currency
                || $item->getAttribute('quantity_source') !== $code
                || (int) $item->getAttribute('rounding_scale') !== 2
                || $item->getAttribute('rounding_method') !==
                    PriceListItem::ROUNDING_METHOD_HALF_UP
                || (int) $item->getAttribute('position') !== $index + 1
                || (
                    ! is_string($unitRate)
                    && ! is_int($unitRate)
                    && ! is_float($unitRate)
                )
                || ! is_numeric($unitRate)
                || (float) $unitRate < 0
            ) {
                throw new ConflictHttpException(
                    'The complete canonical pricing-item set is required before approval.',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function positiveInteger(
        array $input,
        string $key,
    ): int {
        $value = $input[$key] ?? null;

        if (
            is_int($value)
            && $value > 0
        ) {
            return $value;
        }

        if (
            is_string($value)
            && ctype_digit($value)
            && (int) $value > 0
        ) {
            return (int) $value;
        }

        throw new LogicException(
            sprintf(
                'Validated field [%s] must be a positive integer.',
                $key,
            ),
        );
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
                    'Validated field [%s] must be a non-empty string.',
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
                    'Validated field [%s] must be a string or null.',
                    $key,
                ),
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{
     *     code: string,
     *     description: string|null,
     *     unit_rate: string
     * }>
     */
    private function pricingItems(array $input): array
    {
        $value = $input['items'] ?? null;

        if (! is_array($value)) {
            throw new LogicException(
                'Validated pricing items are unavailable.',
            );
        }

        /**
         * @var array<string, array{
         *     code: string,
         *     description: string|null,
         *     unit_rate: string
         * }> $itemsByCode
         */
        $itemsByCode = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                throw new LogicException(
                    'A validated pricing item is invalid.',
                );
            }

            $code = $item['code'] ?? null;

            if (
                ! is_string($code)
                || ! in_array(
                    $code,
                    PriceListItem::CODES,
                    true,
                )
            ) {
                throw new LogicException(
                    'A validated pricing code is invalid.',
                );
            }

            if (array_key_exists($code, $itemsByCode)) {
                throw new LogicException(
                    'Validated pricing codes must be unique.',
                );
            }

            $description = $item['description'] ?? null;

            if (
                $description !== null
                && ! is_string($description)
            ) {
                throw new LogicException(
                    'A validated pricing description is invalid.',
                );
            }

            $unitRate = $item['unit_rate'] ?? null;

            if (
                ! is_int($unitRate)
                && ! is_float($unitRate)
                && ! is_string($unitRate)
            ) {
                throw new LogicException(
                    'A validated pricing rate is invalid.',
                );
            }

            if (
                ! is_numeric($unitRate)
                || (float) $unitRate < 0
            ) {
                throw new LogicException(
                    'A validated pricing rate must be non-negative.',
                );
            }

            $itemsByCode[$code] = [
                'code' => $code,
                'description' => $description,
                'unit_rate' => (string) $unitRate,
            ];
        }

        if (
            count($itemsByCode)
            !== count(PriceListItem::CODES)
        ) {
            throw new LogicException(
                'The complete pricing-item set is required.',
            );
        }

        $orderedItems = [];

        foreach (PriceListItem::CODES as $code) {
            if (! array_key_exists($code, $itemsByCode)) {
                throw new LogicException(
                    'The complete pricing-item set is required.',
                );
            }

            $orderedItems[] = $itemsByCode[$code];
        }

        return $orderedItems;
    }

    private function unitForCode(string $code): string
    {
        return $code === PriceListItem::CODE_ACTUAL_KM
            ? PriceListItem::UNIT_KM
            : PriceListItem::UNIT_PARCEL;
    }

    private function positionForCode(string $code): int
    {
        $position = array_search(
            $code,
            PriceListItem::CODES,
            true,
        );

        if (! is_int($position)) {
            throw new LogicException(
                'The pricing-item position cannot be resolved.',
            );
        }

        return $position + 1;
    }

    private function validationFailure(
        string $field,
        string $message,
    ): never {
        throw ValidationException::withMessages([
            $field => [
                $message,
            ],
        ]);
    }
}
