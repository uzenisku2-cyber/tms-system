<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\PriceListVersion;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class PriceListVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof PriceListVersion) {
            throw new LogicException(
                'PriceListVersionResource requires a PriceListVersion model.',
            );
        }

        $version = $this->resource;

        return [
            'version_number' => (int) $version->getAttribute(
                'version_number',
            ),
            'status' => (string) $version->getAttribute(
                'status',
            ),
            'valid_from' => $this->formatDate(
                $version->getAttribute('valid_from'),
            ),
            'valid_until' => $this->formatDate(
                $version->getAttribute('valid_until'),
            ),
            'change_reason' => $version->getAttribute(
                'change_reason',
            ),
            'approved_at' => $this->formatDateTime(
                $version->getAttribute('approved_at'),
            ),
            'activated_at' => $this->formatDateTime(
                $version->getAttribute('activated_at'),
            ),
            'created_at' => $this->formatDateTime(
                $version->getAttribute('created_at'),
            ),
            'items' => PriceListItemResource::collection(
                $version->items,
            ),
        ];
    }

    private function formatDate(
        mixed $value,
    ): ?string {
        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : null;
    }

    private function formatDateTime(
        mixed $value,
    ): ?string {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
