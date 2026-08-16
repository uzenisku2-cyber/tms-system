<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\DriverPriceListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverPriceListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverPriceListItem) {
            throw new LogicException(
                'DriverPriceListItemResource requires a DriverPriceListItem model.',
            );
        }

        $item = $this->resource;

        return [
            'code' => (string) $item->getAttribute('code'),
            'description' => $item->getAttribute('description'),
            'calculation_method' => (string) $item->getAttribute(
                'calculation_method',
            ),
            'unit' => (string) $item->getAttribute('unit'),
            'unit_rate' => (string) $item->getAttribute('unit_rate'),
            'currency' => (string) $item->getAttribute('currency'),
            'quantity_source' => (string) $item->getAttribute(
                'quantity_source',
            ),
            'rounding_scale' => (int) $item->getAttribute(
                'rounding_scale',
            ),
            'rounding_method' => (string) $item->getAttribute(
                'rounding_method',
            ),
            'position' => (int) $item->getAttribute('position'),
        ];
    }
}
