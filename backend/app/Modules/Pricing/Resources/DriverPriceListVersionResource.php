<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\DriverPriceListVersion;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverPriceListVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverPriceListVersion) {
            throw new LogicException(
                'DriverPriceListVersionResource requires a DriverPriceListVersion model.',
            );
        }

        $version = $this->resource;

        $version->loadMissing([
            'conditionalRules.metricComponents',
            'conditionalRules.rewardComponents',
            'conditionalRules.bands',
        ]);

        return [
            'version_number' => (int) $version->getAttribute(
                'version_number',
            ),
            'lock_version' => (int) $version->getAttribute(
                'lock_version',
            ),
            'status' => (string) $version->getAttribute('status'),
            'valid_from' => $this->formatDate(
                $version->getAttribute('valid_from'),
            ),
            'valid_until' => $this->formatDate(
                $version->getAttribute('valid_until'),
            ),
            'change_reason' => $version->getAttribute('change_reason'),
            'approved_at' => $this->formatDateTime(
                $version->getAttribute('approved_at'),
            ),
            'activated_at' => $this->formatDateTime(
                $version->getAttribute('activated_at'),
            ),
            'created_at' => $this->formatDateTime(
                $version->getAttribute('created_at'),
            ),
            'items' => DriverPriceListItemResource::collection(
                $version->items,
            ),
            'conditional_rules' => DriverPriceListConditionalRuleResource::collection(
                $version->conditionalRules,
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
