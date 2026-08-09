<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DailyReportFormConfiguration;
use App\Modules\Organizations\Models\OrganizationRelationship;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

final class DailyReportFormConfigurationResolver
{
    public function ownerOrganizationId(
        int $organizationId,
        string $serviceDate,
    ): int {
        $current = $organizationId;
        $visited = [];

        for ($depth = 0; $depth < 25; $depth++) {
            if (isset($visited[$current])) {
                throw new DomainException(
                    'Organization hierarchy contains a cycle.',
                );
            }

            $visited[$current] = true;

            $parentIds = OrganizationRelationship::query()
                ->where(
                    'target_organization_id',
                    $current,
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->whereIn(
                    'status',
                    [
                        OrganizationRelationship::STATUS_ACTIVE,
                        OrganizationRelationship::STATUS_ENDED,
                    ],
                )
                ->where(
                    static function (Builder $query) use (
                        $serviceDate,
                    ): void {
                        $query
                            ->whereNull('valid_from')
                            ->orWhereDate(
                                'valid_from',
                                '<=',
                                $serviceDate,
                            );
                    },
                )
                ->where(
                    static function (Builder $query) use (
                        $serviceDate,
                    ): void {
                        $query
                            ->whereNull('valid_until')
                            ->orWhereDate(
                                'valid_until',
                                '>=',
                                $serviceDate,
                            );
                    },
                )
                ->pluck('source_organization_id')
                ->map(
                    static fn (mixed $value): int => (int) $value,
                )
                ->unique()
                ->values();

            if ($parentIds->isEmpty()) {
                return $current;
            }

            if ($parentIds->count() !== 1) {
                throw new DomainException(
                    'Organization hierarchy has multiple effective parents.',
                );
            }

            $current = (int) $parentIds->first();
        }

        throw new DomainException(
            'Organization hierarchy depth limit exceeded.',
        );
    }

    public function resolve(
        int $organizationId,
        string $serviceDate,
    ): ?DailyReportFormConfiguration {
        $ownerOrganizationId = $this->ownerOrganizationId(
            $organizationId,
            $serviceDate,
        );

        return DailyReportFormConfiguration::query()
            ->where(
                'organization_id',
                $ownerOrganizationId,
            )
            ->where(
                'valid_from',
                '<=',
                $serviceDate,
            )
            ->where(
                static function (Builder $query) use (
                    $serviceDate,
                ): void {
                    $query
                        ->whereNull('valid_until')
                        ->orWhere(
                            'valid_until',
                            '>=',
                            $serviceDate,
                        );
                },
            )
            ->orderByDesc('version')
            ->first();
    }
}