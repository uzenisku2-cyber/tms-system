<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use App\Modules\DailyReports\Services\DriverQualityProfileResolution;
use App\Modules\DailyReports\Services\DriverQualityProfileResolver;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DriverQualityProfileFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_exposes_profile_version_component_and_binding_contract(): void
    {
        self::assertTrue(Schema::hasColumns(
            'driver_quality_profiles',
            [
                'public_id',
                'organization_id',
                'code',
                'name',
                'status',
                'current_version',
            ],
        ));

        self::assertTrue(Schema::hasColumns(
            'driver_quality_profile_versions',
            [
                'driver_quality_profile_id',
                'version_number',
                'lock_version',
                'status',
                'calculation_method',
                'valid_from',
                'valid_until',
            ],
        ));

        self::assertTrue(Schema::hasColumns(
            'driver_quality_profile_components',
            [
                'driver_quality_profile_version_id',
                'source_code',
                'position',
            ],
        ));

        self::assertTrue(Schema::hasColumns(
            'driver_quality_profile_bindings',
            [
                'organization_id',
                'driver_quality_profile_id',
                'scope_type',
                'scope_key',
                'organization_relationship_id',
                'driver_organization_assignment_id',
                'valid_from',
                'valid_until',
            ],
        ));
    }

    public function test_resolution_precedence_is_driver_carrier_then_organization(): void
    {
        $actor = User::factory()->create();

        $organization = $this->organization(
            'Master organization',
            Organization::TYPE_MASTER,
        );

        $carrier = $this->organization(
            'External carrier',
            Organization::TYPE_CARRIER,
        );

        $driverUser = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Quality',
            'last_name' => 'Driver',
            'license_number' => 'QUALITY-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $carrier->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_OTHER,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $organization->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);

        $organizationProfile = $this->profile(
            $organization,
            $actor,
            'ORG',
            [DriverQualityProfileComponent::SOURCE_DELIVERED],
        );

        $carrierProfile = $this->profile(
            $organization,
            $actor,
            'CARRIER',
            [
                DriverQualityProfileComponent::SOURCE_DELIVERED,
                DriverQualityProfileComponent::SOURCE_REDIRECTED,
            ],
        );

        $driverProfile = $this->profile(
            $organization,
            $actor,
            'DRIVER',
            DriverQualityProfileComponent::SOURCES,
        );

        $this->binding(
            profile: $organizationProfile,
            organization: $organization,
            actor: $actor,
            scopeType: DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            scopeKey: DriverQualityProfileBinding::organizationScopeKey(),
        );

        $this->binding(
            profile: $carrierProfile,
            organization: $organization,
            actor: $actor,
            scopeType: DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
            scopeKey: DriverQualityProfileBinding::carrierScopeKey(
                (int) $relationship->getKey(),
            ),
            relationship: $relationship,
        );

        $this->binding(
            profile: $driverProfile,
            organization: $organization,
            actor: $actor,
            scopeType: DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            scopeKey: DriverQualityProfileBinding::driverScopeKey(
                (int) $assignment->getKey(),
            ),
            assignment: $assignment,
        );

        $resolver = app(DriverQualityProfileResolver::class);
        $date = CarbonImmutable::parse('2026-08-20');

        $driverResolution = $resolver->resolve(
            organizationId: (int) $organization->getKey(),
            serviceDate: $date,
            driverAssignmentId: (int) $assignment->getKey(),
            carrierRelationshipId: (int) $relationship->getKey(),
        );

        self::assertSame(
            DriverQualityProfileResolution::REASON_RESOLVED,
            $driverResolution->reason,
        );
        self::assertSame(
            DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            $driverResolution->scopeType,
        );
        self::assertSame(
            $driverProfile->getKey(),
            $driverResolution->profile?->getKey(),
        );

        $carrierResolution = $resolver->resolve(
            organizationId: (int) $organization->getKey(),
            serviceDate: $date,
            carrierRelationshipId: (int) $relationship->getKey(),
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
            $carrierResolution->scopeType,
        );
        self::assertSame(
            $carrierProfile->getKey(),
            $carrierResolution->profile?->getKey(),
        );

        $organizationResolution = $resolver->resolve(
            organizationId: (int) $organization->getKey(),
            serviceDate: $date,
        );

        self::assertSame(
            DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            $organizationResolution->scopeType,
        );
        self::assertSame(
            $organizationProfile->getKey(),
            $organizationResolution->profile?->getKey(),
        );
    }

    public function test_explicit_binding_without_effective_version_does_not_fall_back(): void
    {
        $actor = User::factory()->create();
        $organization = $this->organization(
            'Version boundary organization',
            Organization::TYPE_MASTER,
        );

        $default = $this->profile(
            $organization,
            $actor,
            'DEFAULT',
            [DriverQualityProfileComponent::SOURCE_DELIVERED],
        );

        $future = $this->profile(
            $organization,
            $actor,
            'FUTURE',
            [DriverQualityProfileComponent::SOURCE_REDIRECTED],
            '2026-09-01',
        );

        $this->binding(
            profile: $default,
            organization: $organization,
            actor: $actor,
            scopeType: DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            scopeKey: DriverQualityProfileBinding::organizationScopeKey(),
        );

        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Future',
            'last_name' => 'Driver',
            'license_number' => 'FUTURE-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-01-01',
            'created_by_user_id' => $actor->getKey(),
        ]);

        $this->binding(
            profile: $future,
            organization: $organization,
            actor: $actor,
            scopeType: DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
            scopeKey: DriverQualityProfileBinding::driverScopeKey(
                (int) $assignment->getKey(),
            ),
            assignment: $assignment,
        );

        $resolution = app(DriverQualityProfileResolver::class)->resolve(
            organizationId: (int) $organization->getKey(),
            serviceDate: CarbonImmutable::parse('2026-08-20'),
            driverAssignmentId: (int) $assignment->getKey(),
        );

        self::assertSame(
            DriverQualityProfileResolution::REASON_VERSION_UNAVAILABLE,
            $resolution->reason,
        );
        self::assertSame(
            $future->getKey(),
            $resolution->profile?->getKey(),
        );
        self::assertNull($resolution->version);
    }

    private function organization(string $name, string $type): Organization
    {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  list<string>  $sources
     */
    private function profile(
        Organization $organization,
        User $actor,
        string $code,
        array $sources,
        string $validFrom = '2026-01-01',
    ): DriverQualityProfile {
        $profile = DriverQualityProfile::query()->create([
            'organization_id' => $organization->getKey(),
            'code' => $code,
            'name' => $code.' profile',
            'status' => DriverQualityProfile::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $version = DriverQualityProfileVersion::query()->create([
            'driver_quality_profile_id' => $profile->getKey(),
            'version_number' => 1,
            'lock_version' => 1,
            'status' => DriverQualityProfileVersion::STATUS_ACTIVE,
            'calculation_method' => DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
            'valid_from' => $validFrom,
            'created_by_user_id' => $actor->getKey(),
            'activated_by_user_id' => $actor->getKey(),
            'activated_at' => $validFrom.' 00:00:00',
        ]);

        foreach ($sources as $index => $source) {
            DriverQualityProfileComponent::query()->create([
                'driver_quality_profile_version_id' => $version->getKey(),
                'source_code' => $source,
                'position' => $index + 1,
            ]);
        }

        return $profile;
    }

    private function binding(
        DriverQualityProfile $profile,
        Organization $organization,
        User $actor,
        string $scopeType,
        string $scopeKey,
        ?OrganizationRelationship $relationship = null,
        ?DriverOrganizationAssignment $assignment = null,
    ): DriverQualityProfileBinding {
        return DriverQualityProfileBinding::query()->create([
            'organization_id' => $organization->getKey(),
            'driver_quality_profile_id' => $profile->getKey(),
            'scope_type' => $scopeType,
            'scope_key' => $scopeKey,
            'organization_relationship_id' => $relationship?->getKey(),
            'driver_organization_assignment_id' => $assignment?->getKey(),
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);
    }
}
