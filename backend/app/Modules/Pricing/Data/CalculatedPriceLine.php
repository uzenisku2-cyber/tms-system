<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

final readonly class CalculatedPriceLine
{
    public function __construct(
        public int $priceListItemId,
        public string $pricingCode,
        public ?string $description,
        public string $quantity,
        public string $unit,
        public string $unitRate,
        public string $currency,
        public string $lineAmount,
        public string $sourceField,
        public int $roundingScale,
        public string $roundingMethod,
        public int $position,
    ) {}

    /**
     * @return array{
     *     price_list_item_id: int,
     *     pricing_code: string,
     *     description: string|null,
     *     quantity: string,
     *     unit: string,
     *     unit_rate: string,
     *     currency: string,
     *     line_amount: string,
     *     source_field: string,
     *     rounding_scale: int,
     *     rounding_method: string,
     *     position: int
     * }
     */
    public function toArray(): array
    {
        return [
            'price_list_item_id' => $this->priceListItemId,
            'pricing_code' => $this->pricingCode,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_rate' => $this->unitRate,
            'currency' => $this->currency,
            'line_amount' => $this->lineAmount,
            'source_field' => $this->sourceField,
            'rounding_scale' => $this->roundingScale,
            'rounding_method' => $this->roundingMethod,
            'position' => $this->position,
        ];
    }
}
