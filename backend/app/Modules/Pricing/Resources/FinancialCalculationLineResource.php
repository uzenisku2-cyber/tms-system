<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\FinancialCalculationLine;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FinancialCalculationLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FinancialCalculationLine) {
            throw new LogicException(
                'FinancialCalculationLineResource requires a FinancialCalculationLine model.',
            );
        }

        $line = $this->resource;

        return [
            'pricing_code' => (string) $line->getAttribute(
                'pricing_code',
            ),
            'description' => $line->getAttribute(
                'description',
            ),
            'quantity' => (string) $line->getAttribute(
                'quantity',
            ),
            'unit' => (string) $line->getAttribute(
                'unit',
            ),
            'unit_rate' => (string) $line->getAttribute(
                'unit_rate',
            ),
            'currency' => (string) $line->getAttribute(
                'currency',
            ),
            'line_amount' => (string) $line->getAttribute(
                'line_amount',
            ),
            'source_field' => (string) $line->getAttribute(
                'source_field',
            ),
            'rounding_scale' => (int) $line->getAttribute(
                'rounding_scale',
            ),
            'rounding_method' => (string) $line->getAttribute(
                'rounding_method',
            ),
            'position' => (int) $line->getAttribute(
                'position',
            ),
            'created_at' => $this->formatDateTime(
                $line->getAttribute('created_at'),
            ),
        ];
    }

    private function formatDateTime(
        mixed $value,
    ): ?string {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
