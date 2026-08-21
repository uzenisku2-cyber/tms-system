<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class DriverQualityProfileResolver
{
    public function resolve(
        int $organizationId,
        DateTimeInterface $serviceDate,
        ?int $driverAssignmentId = null,
        ?int $carrierRelationshipId = null,
    ): DriverQualityProfileResolution {
        $date = $serviceDate->format('Y-m-d');

        foreach (
            $this->scopeCandidates(
                $driverAssignmentId,
                $carrierRelationshipId,
            ) as $scope
        ) {
            $binding = DriverQualityProfileBinding::query()
                ->with('profile')
                ->where('organization_id', $organizationId)
                ->where('scope_type', $scope['type'])
                ->where('scope_key', $scope['key'])
                ->whereDate('valid_from', '<=', $date)
                ->where(static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                })
                ->orderByDesc('valid_from')
                ->orderByDesc('id')
                ->first();

            if (! $binding instanceof DriverQualityProfileBinding) {
                continue;
            }

            $profile = $binding->profile;

            if (
                ! $profile instanceof DriverQualityProfile
                || (int) $profile->organization_id !== $organizationId
                || ! $profile->isActive()
            ) {
                return new DriverQualityProfileResolution(
                    reason: DriverQualityProfileResolution::REASON_PROFILE_UNAVAILABLE,
                    scopeType: $scope['type'],
                    binding: $binding,
                    profile: $profile instanceof DriverQualityProfile
                        ? $profile
                        : null,
                );
            }

            $version = DriverQualityProfileVersion::query()
                ->with('components')
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
                ->where(static function (Builder $query) use ($date): void {
                    $query->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $date);
                })
                ->orderByDesc('valid_from')
                ->orderByDesc('version_number')
                ->first();

            return new DriverQualityProfileResolution(
                reason: $version instanceof DriverQualityProfileVersion
                    ? DriverQualityProfileResolution::REASON_RESOLVED
                    : DriverQualityProfileResolution::REASON_VERSION_UNAVAILABLE,
                scopeType: $scope['type'],
                binding: $binding,
                profile: $profile,
                version: $version,
            );
        }

        return new DriverQualityProfileResolution(
            reason: DriverQualityProfileResolution::REASON_UNCONFIGURED,
        );
    }

    /**
     * @return list<array{type:string, key:string}>
     */
    private function scopeCandidates(
        ?int $driverAssignmentId,
        ?int $carrierRelationshipId,
    ): array {
        $scopes = [];

        if ($driverAssignmentId !== null && $driverAssignmentId > 0) {
            $scopes[] = [
                'type' => DriverQualityProfileBinding::SCOPE_DRIVER_ASSIGNMENT,
                'key' => DriverQualityProfileBinding::driverScopeKey(
                    $driverAssignmentId,
                ),
            ];
        }

        if ($carrierRelationshipId !== null && $carrierRelationshipId > 0) {
            $scopes[] = [
                'type' => DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP,
                'key' => DriverQualityProfileBinding::carrierScopeKey(
                    $carrierRelationshipId,
                ),
            ];
        }

        $scopes[] = [
            'type' => DriverQualityProfileBinding::SCOPE_ORGANIZATION,
            'key' => DriverQualityProfileBinding::organizationScopeKey(),
        ];

        return $scopes;
    }
}
