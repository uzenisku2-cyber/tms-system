<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverQualityProfileBindingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverQualityProfileBinding) {
            throw new LogicException(
                'DriverQualityProfileBindingResource requires a DriverQualityProfileBinding model.',
            );
        }

        $binding = $this->resource;
        $binding->loadMissing([
            'profile',
            'carrierRelationship.targetOrganization',
            'driverAssignment.driver',
        ]);

        $profile = $binding->profile;

        return [
            'scope_type' => (string) $binding->getAttribute(
                'scope_type',
            ),
            'scope_key' => (string) $binding->getAttribute(
                'scope_key',
            ),
            'organization_relationship_id' => $this->integerOrNull(
                $binding->getAttribute('organization_relationship_id'),
            ),
            'driver_organization_assignment_id' => $this->integerOrNull(
                $binding->getAttribute(
                    'driver_organization_assignment_id',
                ),
            ),
            'scope_label' => $this->scopeLabel($binding),
            'profile' => $profile instanceof DriverQualityProfile
                ? [
                    'public_id' => (string) $profile->getAttribute(
                        'public_id',
                    ),
                    'code' => (string) $profile->getAttribute('code'),
                    'name' => (string) $profile->getAttribute('name'),
                    'status' => (string) $profile->getAttribute(
                        'status',
                    ),
                ]
                : null,
            'valid_from' => $this->formatDate(
                $binding->getAttribute('valid_from'),
            ),
            'valid_until' => $this->formatDate(
                $binding->getAttribute('valid_until'),
            ),
        ];
    }

    private function scopeLabel(
        DriverQualityProfileBinding $binding,
    ): ?string {
        if (
            $binding->scope_type
            === DriverQualityProfileBinding::SCOPE_ORGANIZATION
        ) {
            return 'Výchozí nastavení organizace';
        }

        if (
            $binding->scope_type
            === DriverQualityProfileBinding::SCOPE_CARRIER_RELATIONSHIP
        ) {
            $organization = $binding
                ->carrierRelationship?->targetOrganization;

            return $organization === null
                ? null
                : (string) $organization->getAttribute('name');
        }

        $driver = $binding->driverAssignment?->driver;

        if ($driver === null) {
            return null;
        }

        return trim(
            (string) $driver->getAttribute('first_name')
            .' '
            .(string) $driver->getAttribute('last_name'),
        );
    }

    private function integerOrNull(mixed $value): ?int
    {
        return $value === null
            ? null
            : (int) $value;
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : null;
    }
}
