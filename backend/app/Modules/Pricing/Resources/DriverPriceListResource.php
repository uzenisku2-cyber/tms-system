<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Pricing\Models\DriverPriceList;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class DriverPriceListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DriverPriceList) {
            throw new LogicException(
                'DriverPriceListResource requires a DriverPriceList model.',
            );
        }

        $priceList = $this->resource;

        return [
            'public_id' => (string) $priceList->getAttribute(
                'public_id',
            ),
            'driver_organization_assignment_id' => (int) $priceList->getAttribute(
                'driver_organization_assignment_id',
            ),
            'managed_by_organization_id' => (int) $priceList->getAttribute(
                'managed_by_organization_id',
            ),
            'code' => (string) $priceList->getAttribute(
                'code',
            ),
            'name' => (string) $priceList->getAttribute(
                'name',
            ),
            'description' => $priceList->getAttribute(
                'description',
            ),
            'currency' => (string) $priceList->getAttribute(
                'currency',
            ),
            'status' => (string) $priceList->getAttribute(
                'status',
            ),
            'current_version' => (int) $priceList->getAttribute(
                'current_version',
            ),
            'created_at' => $this->formatDateTime(
                $priceList->getAttribute('created_at'),
            ),
            'updated_at' => $this->formatDateTime(
                $priceList->getAttribute('updated_at'),
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
