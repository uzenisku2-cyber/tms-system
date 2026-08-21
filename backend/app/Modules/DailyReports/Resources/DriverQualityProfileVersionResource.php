<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverQualityProfileVersionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverQualityProfileVersion) {
            throw new LogicException(
                'DriverQualityProfileVersionResource requires a DriverQualityProfileVersion model.',
            );
        }

        $version = $this->resource;
        $version->loadMissing('components');

        return [
            'version_number' => (int) $version->getAttribute(
                'version_number',
            ),
            'lock_version' => (int) $version->getAttribute(
                'lock_version',
            ),
            'status' => (string) $version->getAttribute('status'),
            'calculation_method' => (string) $version->getAttribute(
                'calculation_method',
            ),
            'numerator_sources' => $version->components
                ->pluck('source_code')
                ->map(
                    static fn (mixed $source): string => (string) $source,
                )
                ->values()
                ->all(),
            'denominator_source' => 'loaded_parcels',
            'valid_from' => $this->formatDate(
                $version->getAttribute('valid_from'),
            ),
            'valid_until' => $this->formatDate(
                $version->getAttribute('valid_until'),
            ),
            'change_reason' => $version->getAttribute('change_reason'),
            'activated_at' => $this->formatDateTime(
                $version->getAttribute('activated_at'),
            ),
            'created_at' => $this->formatDateTime(
                $version->getAttribute('created_at'),
            ),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : null;
    }

    private function formatDateTime(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
