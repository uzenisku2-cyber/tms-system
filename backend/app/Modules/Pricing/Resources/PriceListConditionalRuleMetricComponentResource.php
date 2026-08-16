<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\PriceListConditionalRuleMetricComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class PriceListConditionalRuleMetricComponentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (
            ! $this->resource instanceof PriceListConditionalRuleMetricComponent
        ) {
            throw new LogicException(
                'Metric component resource requires a metric component model.',
            );
        }

        $component = $this->resource;

        return [
            'role' => (string) $component->getAttribute(
                'component_role',
            ),
            'source' => (string) $component->getAttribute(
                'metric_source',
            ),
            'position' => (int) $component->getAttribute(
                'position',
            ),
        ];
    }
}
