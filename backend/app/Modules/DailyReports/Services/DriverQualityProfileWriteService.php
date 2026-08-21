<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Models\User;
use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DriverQualityProfileWriteService
{
    public function __construct(
        private readonly DriverSupervisoryAuthorizationService $authorization,
    ) {}

    /**
     * @param  array{
     *     code:string,
     *     name:string,
     *     description?:string|null,
     *     calculation_method:string,
     *     numerator_sources:list<string>,
     *     change_reason?:string|null
     * }  $data
     */
    public function createDraft(
        User $actor,
        int $organizationId,
        array $data,
    ): DriverQualityProfile {
        return DB::transaction(function () use (
            $actor,
            $organizationId,
            $data,
        ): DriverQualityProfile {
            $this->assertUniqueCode(
                $organizationId,
                $data['code'],
            );

            $sources = $this->sources($data);

            $this->assertFormula(
                $data['calculation_method'],
                $sources,
            );

            $profile = DriverQualityProfile::query()->create([
                'organization_id' => $organizationId,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => DriverQualityProfile::STATUS_ACTIVE,
                'current_version' => 1,
                'created_by_user_id' => $actor->getKey(),
            ]);

            $version = DriverQualityProfileVersion::query()->create([
                'driver_quality_profile_id' => $profile->getKey(),
                'version_number' => 1,
                'lock_version' => 1,
                'status' => DriverQualityProfileVersion::STATUS_DRAFT,
                'calculation_method' => $data['calculation_method'],
                'valid_from' => null,
                'valid_until' => null,
                'change_reason' => $data['change_reason'] ?? null,
                'created_by_user_id' => $actor->getKey(),
            ]);

            $this->replaceComponents($version, $sources);

            return $profile->load('versions.components');
        });
    }

    /**
     * @param  array{change_reason?:string|null}  $data
     */
    public function createDraftVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        array $data,
    ): DriverQualityProfileVersion {
        return DB::transaction(function () use (
            $actor,
            $organizationId,
            $publicId,
            $data,
        ): DriverQualityProfileVersion {
            $profile = $this->lockedProfile(
                $organizationId,
                $publicId,
            );

            $this->assertActiveProfile($profile);

            if (
                DriverQualityProfileVersion::query()
                    ->where(
                        'driver_quality_profile_id',
                        $profile->getKey(),
                    )
                    ->where(
                        'status',
                        DriverQualityProfileVersion::STATUS_DRAFT,
                    )
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'profile' => [
                        'Profile already has a draft version.',
                    ],
                ]);
            }

            $source = DriverQualityProfileVersion::query()
                ->with('components')
                ->where(
                    'driver_quality_profile_id',
                    $profile->getKey(),
                )
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->firstOrFail();

            $versionNumber = (int) $source->version_number + 1;

            $version = DriverQualityProfileVersion::query()->create([
                'driver_quality_profile_id' => $profile->getKey(),
                'version_number' => $versionNumber,
                'lock_version' => 1,
                'status' => DriverQualityProfileVersion::STATUS_DRAFT,
                'calculation_method' => $source->calculation_method,
                'valid_from' => null,
                'valid_until' => null,
                'change_reason' => $data['change_reason'] ?? null,
                'created_by_user_id' => $actor->getKey(),
            ]);

            /** @var list<string> $sources */
            $sources = $source->components
                ->pluck('source_code')
                ->map(
                    static fn (mixed $value): string => (string) $value,
                )
                ->values()
                ->all();

            $this->replaceComponents($version, $sources);

            $profile->forceFill([
                'current_version' => $versionNumber,
            ])->save();

            return $version->load('components');
        });
    }

    /**
     * @param  array{
     *     lock_version:int,
     *     calculation_method:string,
     *     numerator_sources:list<string>,
     *     change_reason?:string|null
     * }  $data
     */
    public function updateDraftVersion(
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverQualityProfileVersion {
        return DB::transaction(function () use (
            $organizationId,
            $publicId,
            $versionNumber,
            $data,
        ): DriverQualityProfileVersion {
            $profile = $this->lockedProfile(
                $organizationId,
                $publicId,
            );
            $version = $this->lockedVersion(
                $profile,
                $versionNumber,
            );

            $this->assertDraft($version);
            $this->assertLockVersion(
                $version,
                $data['lock_version'],
            );

            $sources = $this->sources($data);

            $this->assertFormula(
                $data['calculation_method'],
                $sources,
            );

            $version->forceFill([
                'calculation_method' => $data[
                    'calculation_method'
                ],
                'change_reason' => $data['change_reason'] ?? null,
                'lock_version' => (int) $version->lock_version + 1,
            ])->save();

            $this->replaceComponents($version, $sources);

            return $version->load('components');
        });
    }

    /**
     * @param  array{lock_version:int, valid_from:string}  $data
     */
    public function activateDraftVersion(
        User $actor,
        int $organizationId,
        string $publicId,
        int $versionNumber,
        array $data,
    ): DriverQualityProfileVersion {
        return DB::transaction(function () use (
            $actor,
            $organizationId,
            $publicId,
            $versionNumber,
            $data,
        ): DriverQualityProfileVersion {
            $profile = $this->lockedProfile(
                $organizationId,
                $publicId,
            );
            $version = $this->lockedVersion(
                $profile,
                $versionNumber,
            );

            $this->assertActiveProfile($profile);
            $this->assertDraft($version);
            $this->assertLockVersion(
                $version,
                $data['lock_version'],
            );

            $version->load('components');

            /** @var list<string> $sources */
            $sources = $version->components
                ->pluck('source_code')
                ->map(
                    static fn (mixed $value): string => (string) $value,
                )
                ->values()
                ->all();

            $this->assertFormula(
                (string) $version->calculation_method,
                $sources,
            );

            $validFrom = Carbon::parse(
                $data['valid_from'],
            )->startOfDay();

            $conflict = DriverQualityProfileVersion::query()
                ->where(
                    'driver_quality_profile_id',
                    $profile->getKey(),
                )
                ->where('id', '<>', $version->getKey())
                ->whereNotNull('valid_from')
                ->whereDate(
                    'valid_from',
                    '>=',
                    $validFrom->toDateString(),
                )
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'valid_from' => [
                        'A profile version already starts on or after this month.',
                    ],
                ]);
            }

            $current = DriverQualityProfileVersion::query()
                ->where(
                    'driver_quality_profile_id',
                    $profile->getKey(),
                )
                ->where(
                    'status',
                    DriverQualityProfileVersion::STATUS_ACTIVE,
                )
                ->whereNull('valid_until')
                ->lockForUpdate()
                ->first();

            if ($current instanceof DriverQualityProfileVersion) {
                $current->forceFill([
                    'status' => DriverQualityProfileVersion::STATUS_REPLACED,
                    'valid_until' => $validFrom
                        ->copy()
                        ->subDay()
                        ->toDateString(),
                ])->save();
            }

            $version->forceFill([
                'status' => DriverQualityProfileVersion::STATUS_ACTIVE,
                'valid_from' => $validFrom->toDateString(),
                'valid_until' => null,
                'activated_by_user_id' => $actor->getKey(),
                'activated_at' => now(),
                'lock_version' => (int) $version->lock_version + 1,
            ])->save();

            return $version->load('components');
        });
    }

    public function replaceOrganizationBinding(
        User $actor,
        int $organizationId,
        string $profilePublicId,
        string $validFrom,
    ): DriverQualityProfileBinding {
        return $this->replaceBinding(
            actor: $actor,
            organizationId: $organizationId,
            profilePublicId: $profilePublicId,
            scopeType: DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            scopeKey: DriverQualityProfileBinding::organizationScopeKey(),
            validFrom: $validFrom,
        );
    }

    public function replaceCarrierBinding(
        User $actor,
        int $organizationId,
        int $relationshipId,
        string $profilePublicId,
        string $validFrom,
    ): DriverQualityProfileBinding {
        $date = Carbon::parse($validFrom)->startOfDay();

        $relationship = $this->relationship(
            organizationId: $organizationId,
            relationshipId: $relationshipId,
            moment: $date,
        );

        return $this->replaceBinding(
            actor: $actor,
            organizationId: $organizationId,
            profilePublicId: $profilePublicId,
            scopeType: DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
            scopeKey: DriverQualityProfileBinding::carrierScopeKey(
                $relationshipId,
            ),
            validFrom: $validFrom,
            relationshipId: (int) $relationship->getKey(),
        );
    }

    public function replaceDriverBinding(
        User $actor,
        int $organizationId,
        int $assignmentId,
        string $profilePublicId,
        string $validFrom,
    ): DriverQualityProfileBinding {
        $date = Carbon::parse($validFrom)->startOfDay();
        $visibleIds = $this->authorization
            ->visibleDriverOrganizationAssignmentIds(
                actor: $actor,
                organizationId: $organizationId,
                requiredPermission: 'daily-reports.review',
                moment: $date,
            );

        if (! in_array($assignmentId, $visibleIds, true)) {
            abort(404);
        }

        return $this->replaceBinding(
            actor: $actor,
            organizationId: $organizationId,
            profilePublicId: $profilePublicId,
            scopeType: DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            scopeKey: DriverQualityProfileBinding::driverScopeKey(
                $assignmentId,
            ),
            validFrom: $validFrom,
            assignmentId: $assignmentId,
        );
    }

    /**
     * @return array{
     *     ended:bool,
     *     deleted_binding:bool,
     *     scope_type:string,
     *     inheritance_from:string
     * }
     */
    public function endOrganizationBinding(
        int $organizationId,
        string $effectiveFrom,
    ): array {
        return $this->endBinding(
            organizationId: $organizationId,
            scopeType: DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            scopeKey: DriverQualityProfileBinding::organizationScopeKey(),
            effectiveFrom: $effectiveFrom,
        );
    }

    /**
     * @return array{
     *     ended:bool,
     *     deleted_binding:bool,
     *     scope_type:string,
     *     inheritance_from:string
     * }
     */
    public function endCarrierBinding(
        int $organizationId,
        int $relationshipId,
        string $effectiveFrom,
    ): array {
        OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->where('source_organization_id', $organizationId)
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->firstOrFail();

        return $this->endBinding(
            organizationId: $organizationId,
            scopeType: DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
            scopeKey: DriverQualityProfileBinding::carrierScopeKey(
                $relationshipId,
            ),
            effectiveFrom: $effectiveFrom,
        );
    }

    /**
     * @return array{
     *     ended:bool,
     *     deleted_binding:bool,
     *     scope_type:string,
     *     inheritance_from:string
     * }
     */
    public function endDriverBinding(
        User $actor,
        int $organizationId,
        int $assignmentId,
        string $effectiveFrom,
    ): array {
        $visibleIds = $this->authorization
            ->visibleDriverOrganizationAssignmentIds(
                actor: $actor,
                organizationId: $organizationId,
                requiredPermission: 'daily-reports.review',
            );

        if (! in_array($assignmentId, $visibleIds, true)) {
            abort(404);
        }

        return $this->endBinding(
            organizationId: $organizationId,
            scopeType: DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            scopeKey: DriverQualityProfileBinding::driverScopeKey(
                $assignmentId,
            ),
            effectiveFrom: $effectiveFrom,
        );
    }

    private function replaceBinding(
        User $actor,
        int $organizationId,
        string $profilePublicId,
        string $scopeType,
        string $scopeKey,
        string $validFrom,
        ?int $relationshipId = null,
        ?int $assignmentId = null,
    ): DriverQualityProfileBinding {
        return DB::transaction(function () use (
            $actor,
            $organizationId,
            $profilePublicId,
            $scopeType,
            $scopeKey,
            $validFrom,
            $relationshipId,
            $assignmentId,
        ): DriverQualityProfileBinding {
            $profile = $this->lockedProfile(
                $organizationId,
                $profilePublicId,
            );

            $this->assertActiveProfile($profile);

            $start = Carbon::parse($validFrom)->startOfDay();
            $this->assertEffectiveVersion(
                $profile,
                $start,
            );

            $latest = DriverQualityProfileBinding::query()
                ->where('organization_id', $organizationId)
                ->where('scope_key', $scopeKey)
                ->orderByDesc('valid_from')
                ->lockForUpdate()
                ->first();

            if ($latest instanceof DriverQualityProfileBinding) {
                $latestFrom = Carbon::parse(
                    (string) $latest->valid_from,
                )->startOfDay();

                if ($start->isBefore($latestFrom)) {
                    throw ValidationException::withMessages([
                        'valid_from' => [
                            'A binding cannot be inserted before the latest configured month.',
                        ],
                    ]);
                }

                if ($start->equalTo($latestFrom)) {
                    $latest->forceFill([
                        'driver_quality_profile_id' => $profile->getKey(),
                        'created_by_user_id' => $actor->getKey(),
                    ])->save();

                    return $latest->load([
                        'profile',
                        'carrierRelationship.targetOrganization',
                        'driverAssignment.driver',
                    ]);
                }

                $latest->forceFill([
                    'valid_until' => $start
                        ->copy()
                        ->subDay()
                        ->toDateString(),
                ])->save();
            }

            $binding = DriverQualityProfileBinding::query()->create([
                'organization_id' => $organizationId,
                'driver_quality_profile_id' => $profile->getKey(),
                'scope_type' => $scopeType,
                'scope_key' => $scopeKey,
                'organization_relationship_id' => $relationshipId,
                'driver_organization_assignment_id' => $assignmentId,
                'valid_from' => $start->toDateString(),
                'valid_until' => null,
                'created_by_user_id' => $actor->getKey(),
            ]);

            return $binding->load([
                'profile',
                'carrierRelationship.targetOrganization',
                'driverAssignment.driver',
            ]);
        });
    }

    /**
     * @return array{
     *     ended:bool,
     *     deleted_binding:bool,
     *     scope_type:string,
     *     inheritance_from:string
     * }
     */
    private function endBinding(
        int $organizationId,
        string $scopeType,
        string $scopeKey,
        string $effectiveFrom,
    ): array {
        return DB::transaction(function () use (
            $organizationId,
            $scopeType,
            $scopeKey,
            $effectiveFrom,
        ): array {
            $binding = DriverQualityProfileBinding::query()
                ->where('organization_id', $organizationId)
                ->where('scope_type', $scopeType)
                ->where('scope_key', $scopeKey)
                ->orderByDesc('valid_from')
                ->lockForUpdate()
                ->firstOrFail();

            if ($binding->valid_until !== null) {
                throw ValidationException::withMessages([
                    'effective_from' => [
                        'The latest binding is already ended.',
                    ],
                ]);
            }

            $start = Carbon::parse($effectiveFrom)->startOfDay();
            $bindingFrom = Carbon::parse(
                (string) $binding->valid_from,
            )->startOfDay();

            if ($start->isBefore($bindingFrom)) {
                throw ValidationException::withMessages([
                    'effective_from' => [
                        'Binding inheritance cannot resume before the latest binding starts.',
                    ],
                ]);
            }

            if ($start->equalTo($bindingFrom)) {
                $binding->delete();

                return [
                    'ended' => true,
                    'deleted_binding' => true,
                    'scope_type' => $scopeType,
                    'inheritance_from' => $start->toDateString(),
                ];
            }

            $binding->forceFill([
                'valid_until' => $start
                    ->copy()
                    ->subDay()
                    ->toDateString(),
            ])->save();

            return [
                'ended' => true,
                'deleted_binding' => false,
                'scope_type' => $scopeType,
                'inheritance_from' => $start->toDateString(),
            ];
        });
    }

    private function lockedProfile(
        int $organizationId,
        string $publicId,
    ): DriverQualityProfile {
        return DriverQualityProfile::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockedVersion(
        DriverQualityProfile $profile,
        int $versionNumber,
    ): DriverQualityProfileVersion {
        return DriverQualityProfileVersion::query()
            ->where(
                'driver_quality_profile_id',
                $profile->getKey(),
            )
            ->where('version_number', $versionNumber)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertUniqueCode(
        int $organizationId,
        string $code,
    ): void {
        if (
            DriverQualityProfile::query()
                ->where('organization_id', $organizationId)
                ->where('code', $code)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'code' => [
                    'The profile code is already used in this organization.',
                ],
            ]);
        }
    }

    private function assertActiveProfile(
        DriverQualityProfile $profile,
    ): void {
        if (! $profile->isActive()) {
            throw ValidationException::withMessages([
                'profile' => [
                    'Archived profiles cannot be changed or assigned.',
                ],
            ]);
        }
    }

    private function assertDraft(
        DriverQualityProfileVersion $version,
    ): void {
        if (
            $version->status
            !== DriverQualityProfileVersion::STATUS_DRAFT
        ) {
            throw ValidationException::withMessages([
                'version' => [
                    'Only a draft profile version may be changed or activated.',
                ],
            ]);
        }
    }

    private function assertLockVersion(
        DriverQualityProfileVersion $version,
        int $expected,
    ): void {
        if ((int) $version->lock_version !== $expected) {
            throw ValidationException::withMessages([
                'lock_version' => [
                    'The profile version has changed. Reload it and try again.',
                ],
            ]);
        }
    }

    /** @param  list<string>  $sources */
    private function assertFormula(
        string $method,
        array $sources,
    ): void {
        if (
            ! in_array(
                $method,
                DriverQualityProfileVersion::CALCULATION_METHODS,
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'calculation_method' => [
                    'The profile calculation method is not supported.',
                ],
            ]);
        }

        if (
            $method === DriverQualityProfileVersion::METHOD_DISABLED
            && $sources !== []
        ) {
            throw ValidationException::withMessages([
                'numerator_sources' => [
                    'A disabled profile must not select numerator sources.',
                ],
            ]);
        }

        if (
            $method
            === DriverQualityProfileVersion::METHOD_PROCESSED_SHARE
            && ($sources === [] || count($sources) > 3)
        ) {
            throw ValidationException::withMessages([
                'numerator_sources' => [
                    'Processed share requires one to three numerator sources.',
                ],
            ]);
        }

        if (
            count($sources) !== count(array_unique($sources))
            || array_diff(
                $sources,
                DriverQualityProfileComponent::SOURCES,
            ) !== []
        ) {
            throw ValidationException::withMessages([
                'numerator_sources' => [
                    'Numerator sources must be unique canonical parcel metrics.',
                ],
            ]);
        }
    }

    /**
     * @param  array{numerator_sources?:mixed}  $data
     * @return list<string>
     */
    private function sources(array $data): array
    {
        $values = $data['numerator_sources'] ?? [];

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $source): string => (string) $source,
            $values,
        ));
    }

    /** @param  list<string>  $sources */
    private function replaceComponents(
        DriverQualityProfileVersion $version,
        array $sources,
    ): void {
        $version->components()->delete();

        foreach ($sources as $index => $source) {
            $version->components()->create([
                'source_code' => $source,
                'position' => $index + 1,
            ]);
        }
    }

    private function assertEffectiveVersion(
        DriverQualityProfile $profile,
        Carbon $moment,
    ): void {
        $date = $moment->toDateString();

        $exists = DriverQualityProfileVersion::query()
            ->where(
                'driver_quality_profile_id',
                $profile->getKey(),
            )
            ->whereIn('status', [
                DriverQualityProfileVersion::STATUS_ACTIVE,
                DriverQualityProfileVersion::STATUS_REPLACED,
                DriverQualityProfileVersion::STATUS_EXPIRED,
            ])
            ->whereDate('valid_from', '<=', $date)
            ->where(
                static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'profile_public_id' => [
                    'The profile has no active version for the binding month.',
                ],
            ]);
        }
    }

    private function relationship(
        int $organizationId,
        int $relationshipId,
        Carbon $moment,
    ): OrganizationRelationship {
        $date = $moment->toDateString();

        return OrganizationRelationship::query()
            ->whereKey($relationshipId)
            ->where('source_organization_id', $organizationId)
            ->where(
                'relationship_type',
                OrganizationRelationship::TYPE_SUBCONTRACTING,
            )
            ->where(
                'status',
                OrganizationRelationship::STATUS_ACTIVE,
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_from')
                        ->orWhereDate('valid_from', '<=', $date);
                },
            )
            ->where(
                static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                },
            )
            ->firstOrFail();
    }
}
