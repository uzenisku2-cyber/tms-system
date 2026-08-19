<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\PriceList;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class PriceListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof PriceList) {
            throw new LogicException(
                'PriceListResource requires a PriceList model.',
            );
        }

        $priceList = $this->resource;

        $priceList->loadMissing([
            'customerOrganization',
            'providerOrganization',
        ]);

        return [
            'public_id' => (string) $priceList->getAttribute(
                'public_id',
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
            'customer' => $this->organizationSummary(
                $priceList->customerOrganization,
            ),
            'provider' => $this->organizationSummary(
                $priceList->providerOrganization,
            ),
            'created_at' => $this->formatDateTime(
                $priceList->getAttribute('created_at'),
            ),
            'updated_at' => $this->formatDateTime(
                $priceList->getAttribute('updated_at'),
            ),
        ];
    }

    /** @return array{name: string, type: string}|null */
    private function organizationSummary(
        mixed $organization,
    ): ?array {
        if (! $organization instanceof Organization) {
            return null;
        }

        return [
            'name' => (string) $organization->getAttribute(
                'name',
            ),
            'type' => (string) $organization->getAttribute(
                'type',
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
