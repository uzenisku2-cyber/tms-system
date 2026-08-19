<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListConditionalRule;
use App\Modules\Pricing\Models\DriverPriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\DriverPriceListItem;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class DriverPriceListWriteService
{
    public function __construct(
        private readonly DriverSupervisoryAuthorizationService $authorization,
        private readonly ConditionalPriceListRulePayload $conditionalRulePayload,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(
        User $actor,
        int $organizationId,
        array $data,
    ): DriverPriceList {
        if ($organizationId < 1) {
            throw new LogicException(
                'Organization context must be positive.',
            );
        }

        $assignmentId = (int) (
            $data['driver_organization_assignment_id']
            ?? 0
        );

        if ($assignmentId < 1) {
            throw new LogicException(
                'Driver organization assignment is required.',
            );
        }

        $assignment = DriverOrganizationAssignment::query()
            ->whereKey($assignmentId)
            ->firstOrFail();

        $driverId = (int) $assignment->getAttribute(
            'driver_id',
        );

        $targetOrganizationId = (int) $assignment->getAttribute(
            'organization_id',
        );

        $this->authorization->findVisibleDriver(
            actor: $actor,
            organizationId: $organizationId,
            driverId: $driverId,
        );

        $this->authorization->findManageableOrganization(
            actor: $actor,
            organizationId: $organizationId,
            targetOrganizationId: $targetOrganizationId,
        );

        $items = $data['items'] ?? null;

        if (! is_array($items)) {
            throw new LogicException(
                'Driver price-list items are required.',
            );
        }

        $code = $this->requiredString(
            $data,
            'code',
        );

        $name = $this->requiredString(
            $data,
            'name',
        );

        $currency = $this->requiredString(
            $data,
            'currency',
        );

        $description = $this->nullableString(
            $data['description'] ?? null,
        );

        $validFrom = $this->nullableString(
            $data['valid_from'] ?? null,
        );

        $validUntil = $this->nullableString(
            $data['valid_until'] ?? null,
        );

        $changeReason = $this->nullableString(
            $data['change_reason'] ?? null,
        );

        $actorId = (int) $actor->getKey();

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($data);

        return DB::transaction(
            function () use (
                $assignmentId,
                $organizationId,
                $code,
                $name,
                $description,
                $currency,
                $validFrom,
                $validUntil,
                $changeReason,
                $items,
                $actorId,
                $conditionalRules,
            ): DriverPriceList {
                $priceList = DriverPriceList::query()->create([
                    'driver_organization_assignment_id' => $assignmentId,
                    'managed_by_organization_id' => $organizationId,
                    'code' => $code,
                    'name' => $name,
                    'description' => $description,
                    'currency' => $currency,
                    'status' => DriverPriceList::STATUS_DRAFT,
                    'current_version' => 1,
                    'created_by_user_id' => $actorId,
                ]);

                $version = $priceList->versions()->create([
                    'version_number' => 1,
                    'lock_version' => 1,
                    'status' => DriverPriceListVersion::STATUS_DRAFT,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'change_reason' => $changeReason,
                    'created_by_user_id' => $actorId,
                ]);

                $position = 1;

                foreach ($this->orderedItems($items) as $item) {
                    if (! is_array($item)) {
                        throw new LogicException(
                            'Driver price-list item must be an array.',
                        );
                    }

                    $itemCode = (string) (
                        $item['code']
                        ?? ''
                    );

                    if (
                        ! in_array(
                            $itemCode,
                            DriverPriceListItem::CODES,
                            true,
                        )
                    ) {
                        throw new LogicException(
                            'Unsupported driver price-list item code.',
                        );
                    }

                    $unit = $itemCode
                        === DriverPriceListItem::CODE_ACTUAL_KM
                            ? DriverPriceListItem::UNIT_KM
                            : DriverPriceListItem::UNIT_PARCEL;

                    $version->items()->create([
                        'code' => $itemCode,
                        'description' => $this->nullableString(
                            $item['description'] ?? null,
                        ),
                        'calculation_method' => DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
                        'unit' => $unit,
                        'unit_rate' => $item['unit_rate'] ?? 0,
                        'currency' => $currency,
                        'quantity_source' => $itemCode,
                        'rounding_scale' => 2,
                        'rounding_method' => DriverPriceListItem::ROUNDING_METHOD_HALF_UP,
                        'position' => $position,
                    ]);

                    $position++;
                }

                $this->persistConditionalRules(
                    $version,
                    $conditionalRules,
                );

                return $priceList->refresh();
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraftVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        array $data,
    ): DriverPriceListVersion {
        $expectedCurrentVersion = (int) (
            $data['expected_current_version']
            ?? 0
        );

        if ($expectedCurrentVersion < 1) {
            throw new LogicException(
                'Expected current version must be positive.',
            );
        }

        $items = $data['items'] ?? null;

        if (! is_array($items)) {
            throw new LogicException(
                'Driver price-list items are required.',
            );
        }

        $replaceConditionalRules = array_key_exists(
            'conditional_rules',
            $data,
        );

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($data);

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $publicId,
                $data,
                $expectedCurrentVersion,
                $items,
                $replaceConditionalRules,
                $conditionalRules,
            ): DriverPriceListVersion {
                $priceList = $this->findManageablePriceList(
                    actor: $actor,
                    organizationId: $organizationId,
                    publicId: $publicId,
                    lockForUpdate: true,
                );

                if (
                    $priceList->getAttribute('status')
                    === DriverPriceList::STATUS_ARCHIVED
                ) {
                    abort(409, 'Archived driver price lists cannot receive new versions.');
                }

                $currentVersion = (int) $priceList->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $expectedCurrentVersion) {
                    abort(409, 'The driver price-list current version has changed.');
                }

                $current = $priceList
                    ->versions()
                    ->where('version_number', $currentVersion)
                    ->first();

                if (! $current instanceof DriverPriceListVersion) {
                    abort(409, 'The current driver price-list version is unavailable.');
                }

                if (
                    $current->getAttribute('status')
                    === DriverPriceListVersion::STATUS_DRAFT
                ) {
                    abort(409, 'A draft driver price-list version already exists.');
                }

                $nextVersion = $currentVersion + 1;

                $version = $priceList->versions()->create([
                    'version_number' => $nextVersion,
                    'lock_version' => 1,
                    'status' => DriverPriceListVersion::STATUS_DRAFT,
                    'valid_from' => $data['valid_from'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'change_reason' => $data['change_reason'] ?? null,
                    'created_by_user_id' => (int) $actor->getKey(),
                ]);

                $this->replaceItems(
                    version: $version,
                    currency: (string) $priceList->getAttribute('currency'),
                    items: $items,
                );

                if ($replaceConditionalRules) {
                    $this->persistConditionalRules(
                        $version,
                        $conditionalRules,
                    );
                } else {
                    $this->copyConditionalRules(
                        $current,
                        $version,
                    );
                }

                $priceList->forceFill([
                    'current_version' => $nextVersion,
                ])->saveOrFail();

                return $version->fresh(
                    $this->versionRelations(),
                )
                    ?? throw new LogicException(
                        'Created driver price-list version could not be reloaded.',
                    );
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraftVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverPriceListVersion {
        if ($versionNumber < 1) {
            throw new LogicException(
                'Driver price-list version number must be positive.',
            );
        }

        $expectedLockVersion = (int) (
            $data['expected_lock_version']
            ?? 0
        );

        if ($expectedLockVersion < 1) {
            throw new LogicException(
                'Expected lock version must be positive.',
            );
        }

        $items = $data['items'] ?? null;

        if (! is_array($items)) {
            throw new LogicException(
                'Driver price-list items are required.',
            );
        }

        $replaceConditionalRules = array_key_exists(
            'conditional_rules',
            $data,
        );

        $conditionalRules =
            $this->conditionalRulePayload->fromInput($data);

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $publicId,
                $versionNumber,
                $data,
                $expectedLockVersion,
                $items,
                $replaceConditionalRules,
                $conditionalRules,
            ): DriverPriceListVersion {
                $priceList = $this->findManageablePriceList(
                    actor: $actor,
                    organizationId: $organizationId,
                    publicId: $publicId,
                    lockForUpdate: true,
                );

                if (
                    $priceList->getAttribute('status')
                    === DriverPriceList::STATUS_ARCHIVED
                ) {
                    abort(409, 'Archived driver price lists cannot update versions.');
                }

                $currentVersion = (int) $priceList->getAttribute(
                    'current_version',
                );

                if ($currentVersion !== $versionNumber) {
                    abort(409, 'Only the current driver price-list version may be updated.');
                }

                $version = $priceList
                    ->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->first();

                if (! $version instanceof DriverPriceListVersion) {
                    abort(404);
                }

                if (
                    $version->getAttribute('status')
                    !== DriverPriceListVersion::STATUS_DRAFT
                ) {
                    abort(409, 'Only draft driver price-list versions may be updated.');
                }

                $currentLockVersion = (int) $version->getAttribute(
                    'lock_version',
                );

                if ($currentLockVersion !== $expectedLockVersion) {
                    abort(409, 'The driver price-list draft version has changed.');
                }

                $metadata = [];

                if (array_key_exists('name', $data)) {
                    $metadata['name'] = $this->requiredString(
                        $data,
                        'name',
                    );
                }

                if (array_key_exists('description', $data)) {
                    $metadata['description'] = $this->nullableString(
                        $data['description'],
                    );
                }

                if ($metadata !== []) {
                    $priceList->forceFill($metadata)->saveOrFail();
                }

                $version->forceFill([
                    'valid_from' => $data['valid_from'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'change_reason' => $data['change_reason'] ?? null,
                    'lock_version' => $currentLockVersion + 1,
                ])->saveOrFail();

                $this->replaceItems(
                    version: $version,
                    currency: (string) $priceList->getAttribute('currency'),
                    items: $items,
                );

                if ($replaceConditionalRules) {
                    $this->replaceConditionalRules(
                        $version,
                        $conditionalRules,
                    );
                }

                return $version->fresh(
                    $this->versionRelations(),
                )
                    ?? throw new LogicException(
                        'Updated driver price-list version could not be reloaded.',
                    );
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function approveDraftVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverPriceListVersion {
        $expectedLockVersion = $this->positiveInteger(
            data: $data,
            key: 'expected_lock_version',
        );

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
            ): DriverPriceListVersion {
                $priceList = $this->findManageablePriceList(
                    actor: $actor,
                    organizationId: $organizationId,
                    publicId: $publicId,
                    lockForUpdate: true,
                );

                if (
                    $priceList->getAttribute('status')
                    === DriverPriceList::STATUS_ARCHIVED
                ) {
                    throw new ConflictHttpException(
                        'Archived driver price lists cannot approve versions.',
                    );
                }

                $version = $priceList
                    ->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $priceList->getAttribute('current_version')
                    !== $versionNumber
                ) {
                    throw new ConflictHttpException(
                        'Only the current driver price-list version may be approved.',
                    );
                }

                if (
                    $version->getAttribute('status')
                    !== DriverPriceListVersion::STATUS_DRAFT
                ) {
                    throw new ConflictHttpException(
                        'Only draft driver price-list versions may be approved.',
                    );
                }

                if (
                    (int) $version->getAttribute('lock_version')
                    !== $expectedLockVersion
                ) {
                    throw new ConflictHttpException(
                        'The driver price-list draft version has changed.',
                    );
                }

                $this->assertApprovableItems(
                    priceList: $priceList,
                    version: $version,
                );

                $this->assertApprovableConditionalRules(
                    $version,
                );

                $actorId = (int) $actor->getKey();

                if ($actorId < 1) {
                    throw new LogicException(
                        'The authenticated user has no valid identifier.',
                    );
                }

                $version->forceFill([
                    'status' => DriverPriceListVersion::STATUS_APPROVED,
                    'approved_by_user_id' => $actorId,
                    'approved_at' => now(),
                    'activated_at' => null,
                ])->saveOrFail();

                return $version->fresh(
                    $this->versionRelations(),
                )
                    ?? throw new LogicException(
                        'Approved driver price-list version could not be reloaded.',
                    );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function activateApprovedVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverPriceListVersion {
        $expectedLockVersion = $this->positiveInteger(
            data: $data,
            key: 'expected_lock_version',
        );

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
            ): DriverPriceListVersion {
                $priceList = $this->findManageablePriceList(
                    actor: $actor,
                    organizationId: $organizationId,
                    publicId: $publicId,
                    lockForUpdate: true,
                );

                if (
                    $priceList->getAttribute('status')
                    === DriverPriceList::STATUS_ARCHIVED
                ) {
                    throw new ConflictHttpException(
                        'Archived driver price lists cannot activate versions.',
                    );
                }

                $version = $priceList
                    ->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $priceList->getAttribute('current_version')
                    !== $versionNumber
                ) {
                    throw new ConflictHttpException(
                        'Only the current driver price-list version may be activated.',
                    );
                }

                if (
                    $version->getAttribute('status')
                    !== DriverPriceListVersion::STATUS_APPROVED
                ) {
                    throw new ConflictHttpException(
                        'Only approved driver price-list versions may be activated.',
                    );
                }

                if (
                    (int) $version->getAttribute('lock_version')
                    !== $expectedLockVersion
                ) {
                    throw new ConflictHttpException(
                        'The driver price-list approved version has changed.',
                    );
                }

                $approvedByUserId = (int) $version->getAttribute(
                    'approved_by_user_id',
                );
                $approvedAt = $version->getAttribute('approved_at');

                if (
                    $approvedByUserId < 1
                    || ! $approvedAt instanceof DateTimeInterface
                ) {
                    throw new ConflictHttpException(
                        'Only approved driver price-list versions may be activated.',
                    );
                }

                $validFrom = $version->getAttribute('valid_from');
                $validUntil = $version->getAttribute('valid_until');

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

                $activeVersions = $priceList
                    ->versions()
                    ->where(
                        'status',
                        DriverPriceListVersion::STATUS_ACTIVE,
                    )
                    ->where('version_number', '<>', $versionNumber)
                    ->orderBy('version_number')
                    ->lockForUpdate()
                    ->get();

                if ($activeVersions->count() > 1) {
                    throw new ConflictHttpException(
                        'Multiple active driver price-list versions require manual repair.',
                    );
                }

                $activeVersion = $activeVersions->first();

                if (
                    $activeVersion instanceof DriverPriceListVersion
                ) {
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
                            'The active driver price-list version has an invalid effective period.',
                        );
                    }

                    $replacementStart = CarbonImmutable::instance(
                        $validFrom,
                    )->startOfDay();
                    $activeStart = CarbonImmutable::instance(
                        $activeValidFrom,
                    )->startOfDay();
                    $replacementBoundary = $replacementStart->subDay();

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
                        DriverPriceListVersion::STATUS_REPLACED,
                    );
                    $activeVersion->saveOrFail();
                }

                $version->forceFill([
                    'status' => DriverPriceListVersion::STATUS_ACTIVE,
                    'activated_at' => now(),
                ])->saveOrFail();

                $priceList->setAttribute(
                    'status',
                    DriverPriceList::STATUS_ACTIVE,
                );
                $priceList->saveOrFail();

                return $version->fresh(['items'])
                    ?? throw new LogicException(
                        'Activated driver price-list version could not be reloaded.',
                    );
            },
            3,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function expireActiveVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverPriceListVersion {
        $expectedLockVersion = $this->positiveInteger(
            data: $data,
            key: 'expected_lock_version',
        );

        $requestedValidUntil = CarbonImmutable::parse(
            $this->requiredString(
                data: $data,
                key: 'valid_until',
            ),
        )->startOfDay();

        return DB::transaction(
            function () use (
                $actor,
                $organizationId,
                $publicId,
                $versionNumber,
                $expectedLockVersion,
                $requestedValidUntil,
            ): DriverPriceListVersion {
                $priceList = $this->findManageablePriceList(
                    actor: $actor,
                    organizationId: $organizationId,
                    publicId: $publicId,
                    lockForUpdate: true,
                );

                if (
                    $priceList->getAttribute('status')
                    === DriverPriceList::STATUS_ARCHIVED
                ) {
                    throw new ConflictHttpException(
                        'Archived driver price lists cannot expire versions.',
                    );
                }

                if (
                    $priceList->getAttribute('status')
                    !== DriverPriceList::STATUS_ACTIVE
                ) {
                    throw new ConflictHttpException(
                        'Only active driver price lists may expire versions.',
                    );
                }

                $version = $priceList
                    ->versions()
                    ->where('version_number', $versionNumber)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $version->getAttribute('status')
                    !== DriverPriceListVersion::STATUS_ACTIVE
                ) {
                    throw new ConflictHttpException(
                        'Only active driver price-list versions may be expired.',
                    );
                }

                if (
                    (int) $version->getAttribute('lock_version')
                    !== $expectedLockVersion
                ) {
                    throw new ConflictHttpException(
                        'The driver price-list active version has changed.',
                    );
                }

                $otherActiveVersions = $priceList
                    ->versions()
                    ->where(
                        'status',
                        DriverPriceListVersion::STATUS_ACTIVE,
                    )
                    ->where('version_number', '<>', $versionNumber)
                    ->lockForUpdate()
                    ->get();

                if ($otherActiveVersions->isNotEmpty()) {
                    throw new ConflictHttpException(
                        'The driver price-list aggregate contains multiple active versions and requires repair.',
                    );
                }

                $validFrom = $version->getAttribute('valid_from');
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

                if (
                    $requestedValidUntil->isBefore(
                        $validFromDate,
                    )
                ) {
                    throw new ConflictHttpException(
                        'The expiration date cannot precede the active version start date.',
                    );
                }

                if (
                    $requestedValidUntil->isAfter(
                        CarbonImmutable::now()->startOfDay(),
                    )
                ) {
                    throw new ConflictHttpException(
                        'The expiration date cannot be in the future.',
                    );
                }

                if (
                    $storedValidUntil instanceof DateTimeInterface
                ) {
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

                $version->forceFill([
                    'status' => DriverPriceListVersion::STATUS_EXPIRED,
                    'valid_until' => $requestedValidUntil,
                ])->saveOrFail();

                return $version->fresh(['items'])
                    ?? throw new LogicException(
                        'Expired driver price-list version could not be reloaded.',
                    );
            },
            3,
        );
    }

    private function persistConditionalRules(
        DriverPriceListVersion $version,
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
                'rounding_method' => DriverPriceListConditionalRule::ROUNDING_METHOD_HALF_UP,
                'position' => $ruleIndex + 1,
            ]);

            foreach ($numeratorSources as $index => $source) {
                $rule->metricComponents()->create([
                    'component_role' => DriverPriceListConditionalRuleMetricComponent::ROLE_NUMERATOR,
                    'metric_source' => $source,
                    'position' => $index + 1,
                ]);
            }

            foreach ($denominatorSources as $index => $source) {
                $rule->metricComponents()->create([
                    'component_role' => DriverPriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR,
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
        DriverPriceListVersion $version,
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

    private function copyConditionalRules(
        DriverPriceListVersion $source,
        DriverPriceListVersion $target,
    ): void {

        $rules = $this->conditionalRulePayload->fromInput([
            'conditional_rules' => $this->conditionalRuleInputFromVersion($source),
        ]);

        $this->persistConditionalRules($target, $rules);
    }

    private function assertApprovableConditionalRules(
        DriverPriceListVersion $version,
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
        DriverPriceListVersion $version,
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
                        DriverPriceListConditionalRuleMetricComponent::ROLE_NUMERATOR
                ) {
                    $numeratorSources[] = $source;

                    continue;
                }

                if (
                    $component->getAttribute('component_role') ===
                        DriverPriceListConditionalRuleMetricComponent::ROLE_DENOMINATOR
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
        DriverPriceList $priceList,
        DriverPriceListVersion $version,
    ): void {
        $items = $version
            ->items()
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        $currency = $priceList->getAttribute('currency');

        if (
            ! is_string($currency)
            || preg_match('/^[A-Z]{3}$/', $currency) !== 1
            || $items->count() !== count(DriverPriceListItem::CODES)
        ) {
            throw new ConflictHttpException(
                'The complete canonical driver pricing-item set is required before approval.',
            );
        }

        foreach (DriverPriceListItem::CODES as $index => $code) {
            $item = $items->get($index);
            $unitRate = $item instanceof DriverPriceListItem
                ? $item->getAttribute('unit_rate')
                : null;

            if (
                ! $item instanceof DriverPriceListItem
                || $item->getAttribute('code') !== $code
                || $item->getAttribute('calculation_method') !==
                    DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                || $item->getAttribute('unit') !==
                    $this->unitForCode($code)
                || $item->getAttribute('currency') !== $currency
                || $item->getAttribute('quantity_source') !== $code
                || (int) $item->getAttribute('rounding_scale') !== 2
                || $item->getAttribute('rounding_method') !==
                    DriverPriceListItem::ROUNDING_METHOD_HALF_UP
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
                    'The complete canonical driver pricing-item set is required before approval.',
                );
            }
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function orderedItems(array $items): array
    {
        $itemsByCode = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new LogicException(
                    'Driver price-list item must be an array.',
                );
            }

            $code = $item['code'] ?? null;

            if (
                ! is_string($code)
                || ! in_array(
                    $code,
                    DriverPriceListItem::CODES,
                    true,
                )
            ) {
                throw new LogicException(
                    'Unsupported driver price-list item code.',
                );
            }

            if (array_key_exists($code, $itemsByCode)) {
                throw new LogicException(
                    'Driver price-list item codes must be unique.',
                );
            }

            $itemsByCode[$code] = $item;
        }

        if (
            count($itemsByCode)
            !== count(DriverPriceListItem::CODES)
        ) {
            throw new LogicException(
                'The complete canonical driver pricing-item set is required.',
            );
        }

        $ordered = [];

        foreach (DriverPriceListItem::CODES as $code) {
            if (! array_key_exists($code, $itemsByCode)) {
                throw new LogicException(
                    'The complete canonical driver pricing-item set is required.',
                );
            }

            $ordered[] = $itemsByCode[$code];
        }

        return $ordered;
    }

    private function unitForCode(string $code): string
    {
        return $code === DriverPriceListItem::CODE_ACTUAL_KM
            ? DriverPriceListItem::UNIT_KM
            : DriverPriceListItem::UNIT_PARCEL;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function positiveInteger(
        array $data,
        string $key,
    ): int {
        $value = $data[$key] ?? null;

        if (is_int($value) && $value > 0) {
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
                'Driver price-list field [%s] must be a positive integer.',
                $key,
            ),
        );
    }

    private function findManageablePriceList(
        User $actor,
        int $organizationId,
        string $publicId,
        bool $lockForUpdate = false,
    ): DriverPriceList {
        $query = DriverPriceList::query()
            ->where('public_id', $publicId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $priceList = $query->firstOrFail();

        if (
            (int) $priceList->getAttribute('managed_by_organization_id')
            !== $organizationId
        ) {
            abort(404);
        }

        $assignment = DriverOrganizationAssignment::query()
            ->whereKey(
                (int) $priceList->getAttribute(
                    'driver_organization_assignment_id',
                ),
            )
            ->firstOrFail();

        $this->authorization->findVisibleDriver(
            actor: $actor,
            organizationId: $organizationId,
            driverId: (int) $assignment->getAttribute('driver_id'),
        );

        $this->authorization->findManageableOrganization(
            actor: $actor,
            organizationId: $organizationId,
            targetOrganizationId: (int) $assignment->getAttribute(
                'organization_id',
            ),
        );

        return $priceList;
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function replaceItems(
        DriverPriceListVersion $version,
        string $currency,
        array $items,
    ): void {
        $version->items()->delete();

        $position = 1;

        foreach ($this->orderedItems($items) as $item) {
            if (! is_array($item)) {
                throw new LogicException(
                    'Driver price-list item must be an array.',
                );
            }

            $itemCode = (string) ($item['code'] ?? '');

            if (! in_array($itemCode, DriverPriceListItem::CODES, true)) {
                throw new LogicException(
                    'Unsupported driver price-list item code.',
                );
            }

            $unit = $itemCode === DriverPriceListItem::CODE_ACTUAL_KM
                ? DriverPriceListItem::UNIT_KM
                : DriverPriceListItem::UNIT_PARCEL;

            $version->items()->create([
                'code' => $itemCode,
                'description' => $this->nullableString(
                    $item['description'] ?? null,
                ),
                'calculation_method' => DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
                'unit' => $unit,
                'unit_rate' => $item['unit_rate'] ?? 0,
                'currency' => $currency,
                'quantity_source' => $itemCode,
                'rounding_scale' => 2,
                'rounding_method' => DriverPriceListItem::ROUNDING_METHOD_HALF_UP,
                'position' => $position,
            ]);

            $position++;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requiredString(
        array $data,
        string $key,
    ): string {
        $value = $data[$key] ?? null;

        if (
            ! is_string($value)
            || $value === ''
        ) {
            throw new LogicException(
                sprintf(
                    'Driver price-list field [%s] is required.',
                    $key,
                ),
            );
        }

        return $value;
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                'Expected a nullable string value.',
            );
        }

        return $value === ''
            ? null
            : $value;
    }
}
