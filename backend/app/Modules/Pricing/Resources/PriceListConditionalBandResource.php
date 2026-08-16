<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\PriceListConditionalBand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class PriceListConditionalBandResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof PriceListConditionalBand) {
            throw new LogicException(
                'Conditional band resource requires a conditional band model.',
            );
        }

        $band = $this->resource;

        return [
            'minimum_value' => $band->getAttribute(
                'minimum_value',
            ),
            'maximum_value' => $band->getAttribute(
                'maximum_value',
            ),
            'minimum_inclusive' => (bool) $band->getAttribute(
                'minimum_inclusive',
            ),
            'maximum_inclusive' => (bool) $band->getAttribute(
                'maximum_inclusive',
            ),
            'adjustment_value' => $band->getAttribute(
                'adjustment_value',
            ),
            'position' => (int) $band->getAttribute(
                'position',
            ),
        ];
    }
}
