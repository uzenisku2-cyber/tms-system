<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\FinancialCalculationEvent;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FinancialCalculationEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FinancialCalculationEvent) {
            throw new LogicException(
                'FinancialCalculationEventResource requires a FinancialCalculationEvent model.',
            );
        }

        $event = $this->resource;

        return [
            'event_type' => (string) $event->getAttribute(
                'event_type',
            ),
            'from_status' => $event->getAttribute(
                'from_status',
            ),
            'to_status' => (string) $event->getAttribute(
                'to_status',
            ),
            'reason' => $event->getAttribute(
                'reason',
            ),
            'created_at' => $this->formatDateTime(
                $event->getAttribute('created_at'),
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
