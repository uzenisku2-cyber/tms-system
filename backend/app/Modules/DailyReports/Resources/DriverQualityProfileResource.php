<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DriverQualityProfile;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverQualityProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverQualityProfile) {
            throw new LogicException(
                'DriverQualityProfileResource requires a DriverQualityProfile model.',
            );
        }

        $profile = $this->resource;
        $profile->loadMissing('versions.components');

        return [
            'public_id' => (string) $profile->getAttribute('public_id'),
            'code' => (string) $profile->getAttribute('code'),
            'name' => (string) $profile->getAttribute('name'),
            'description' => $profile->getAttribute('description'),
            'status' => (string) $profile->getAttribute('status'),
            'current_version' => (int) $profile->getAttribute(
                'current_version',
            ),
            'versions' => DriverQualityProfileVersionResource::collection(
                $profile->versions,
            ),
            'created_at' => $this->formatDateTime(
                $profile->getAttribute('created_at'),
            ),
            'updated_at' => $this->formatDateTime(
                $profile->getAttribute('updated_at'),
            ),
        ];
    }

    private function formatDateTime(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
