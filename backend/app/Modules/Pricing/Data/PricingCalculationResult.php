<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

final readonly class PricingCalculationResult
{
    /**
     * @param  array<string, mixed>  $inputSnapshot
     * @param  list<CalculatedPriceLine>  $lines
     */
    public function __construct(
        public string $currency,
        public array $inputSnapshot,
        public array $lines,
        public string $subtotalAmount,
        public string $totalAmount,
    ) {}

    /**
     * @return list<array{
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
     * }>
     */
    public function linePayloads(): array
    {
        return array_map(
            static fn (
                CalculatedPriceLine $line,
            ): array => $line->toArray(),
            $this->lines,
        );
    }

    /**
     * @return array{
     *     currency: string,
     *     input_snapshot: array<string, mixed>,
     *     subtotal_amount: string,
     *     total_amount: string,
     *     lines: list<array{
     *         price_list_item_id: int,
     *         pricing_code: string,
     *         description: string|null,
     *         quantity: string,
     *         unit: string,
     *         unit_rate: string,
     *         currency: string,
     *         line_amount: string,
     *         source_field: string,
     *         rounding_scale: int,
     *         rounding_method: string,
     *         position: int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'input_snapshot' => $this->inputSnapshot,
            'subtotal_amount' => $this->subtotalAmount,
            'total_amount' => $this->totalAmount,
            'lines' => $this->linePayloads(),
        ];
    }
}
