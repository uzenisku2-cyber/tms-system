<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Data\CalculatedPriceLine;
use App\Modules\Pricing\Data\PricingCalculationResult;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use InvalidArgumentException;
use LogicException;

final class PricingAmountCalculator
{
    private const QUANTITY_SCALE = 3;

    private const RATE_SCALE = 4;

    private const STORED_AMOUNT_SCALE = 2;

    private const PRODUCT_SCALE = 8;

    /** @var array<string, string> */
    private const SOURCE_BY_CODE = [
        PriceListItem::CODE_DELIVERED_PARCELS => PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,

        PriceListItem::CODE_REDIRECTED_PARCELS => PriceListItem::QUANTITY_SOURCE_REDIRECTED_PARCELS,

        PriceListItem::CODE_UNDELIVERED_PARCELS => PriceListItem::QUANTITY_SOURCE_UNDELIVERED_PARCELS,

        PriceListItem::CODE_ACTUAL_KM => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
    ];

    /** @var array<string, string> */
    private const UNIT_BY_CODE = [
        PriceListItem::CODE_DELIVERED_PARCELS => PriceListItem::UNIT_PARCEL,

        PriceListItem::CODE_REDIRECTED_PARCELS => PriceListItem::UNIT_PARCEL,

        PriceListItem::CODE_UNDELIVERED_PARCELS => PriceListItem::UNIT_PARCEL,

        PriceListItem::CODE_ACTUAL_KM => PriceListItem::UNIT_KM,
    ];

    /**
     * @param  array<string, mixed>  $inputSnapshot
     */
    public function calculate(
        PriceListVersion $priceListVersion,
        array $inputSnapshot,
    ): PricingCalculationResult {
        $priceListVersion->loadMissing([
            'priceList',
            'items',
        ]);

        if (
            ! $priceListVersion->isActive()
            && ! $priceListVersion->isReplaced()
        ) {
            throw new LogicException(
                'Only an active or replaced price-list version can be calculated.',
            );
        }

        $priceList = $priceListVersion->priceList;

        if (! $priceList instanceof PriceList) {
            throw new LogicException(
                'The price-list version has no parent price list.',
            );
        }

        if (! $priceList->isActive()) {
            throw new LogicException(
                'Only an active price list can be calculated.',
            );
        }

        $currency = $this->requiredString(
            $priceList->getAttribute('currency'),
            'Price-list currency',
        );

        if ($priceListVersion->items->isEmpty()) {
            throw new LogicException(
                'The price-list version contains no pricing items.',
            );
        }

        $lines = [];
        $subtotalAmount = '0.00';
        $seenCodes = [];
        $seenPositions = [];

        foreach ($priceListVersion->items as $item) {
            $code = $this->requiredString(
                $item->getAttribute('code'),
                'Pricing code',
            );

            if (! array_key_exists($code, self::SOURCE_BY_CODE)) {
                throw new LogicException(
                    sprintf(
                        'Unsupported pricing code [%s].',
                        $code,
                    ),
                );
            }

            if (array_key_exists($code, $seenCodes)) {
                throw new LogicException(
                    sprintf(
                        'Duplicate pricing code [%s].',
                        $code,
                    ),
                );
            }

            $position = $this->positiveInteger(
                $item->getAttribute('position'),
                sprintf(
                    'Position for pricing code [%s]',
                    $code,
                ),
            );

            if (array_key_exists($position, $seenPositions)) {
                throw new LogicException(
                    sprintf(
                        'Duplicate pricing position [%d].',
                        $position,
                    ),
                );
            }

            $calculationMethod = $this->requiredString(
                $item->getAttribute('calculation_method'),
                sprintf(
                    'Calculation method for pricing code [%s]',
                    $code,
                ),
            );

            if (
                $calculationMethod !==
                PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
            ) {
                throw new LogicException(
                    sprintf(
                        'Unsupported calculation method [%s].',
                        $calculationMethod,
                    ),
                );
            }

            $sourceField = $this->requiredString(
                $item->getAttribute('quantity_source'),
                sprintf(
                    'Quantity source for pricing code [%s]',
                    $code,
                ),
            );

            if ($sourceField !== self::SOURCE_BY_CODE[$code]) {
                throw new LogicException(
                    sprintf(
                        'Pricing code [%s] has an invalid quantity source [%s].',
                        $code,
                        $sourceField,
                    ),
                );
            }

            $unit = $this->requiredString(
                $item->getAttribute('unit'),
                sprintf(
                    'Unit for pricing code [%s]',
                    $code,
                ),
            );

            if ($unit !== self::UNIT_BY_CODE[$code]) {
                throw new LogicException(
                    sprintf(
                        'Pricing code [%s] has an invalid unit [%s].',
                        $code,
                        $unit,
                    ),
                );
            }

            $itemCurrency = $this->requiredString(
                $item->getAttribute('currency'),
                sprintf(
                    'Currency for pricing code [%s]',
                    $code,
                ),
            );

            if ($itemCurrency !== $currency) {
                throw new LogicException(
                    sprintf(
                        'Pricing code [%s] uses currency [%s] instead of [%s].',
                        $code,
                        $itemCurrency,
                        $currency,
                    ),
                );
            }

            $roundingMethod = $this->requiredString(
                $item->getAttribute('rounding_method'),
                sprintf(
                    'Rounding method for pricing code [%s]',
                    $code,
                ),
            );

            if (
                $roundingMethod !==
                PriceListItem::ROUNDING_METHOD_HALF_UP
            ) {
                throw new LogicException(
                    sprintf(
                        'Unsupported rounding method [%s].',
                        $roundingMethod,
                    ),
                );
            }

            $roundingScale = $this->nonNegativeInteger(
                $item->getAttribute('rounding_scale'),
                sprintf(
                    'Rounding scale for pricing code [%s]',
                    $code,
                ),
            );

            if ($roundingScale > 6) {
                throw new LogicException(
                    sprintf(
                        'Rounding scale [%d] exceeds the supported maximum.',
                        $roundingScale,
                    ),
                );
            }

            $quantity = $this->quantityFromSnapshot(
                $inputSnapshot,
                $sourceField,
            );

            $unitRate = $this->normalizeDecimal(
                $item->getAttribute('unit_rate'),
                self::RATE_SCALE,
                sprintf(
                    'Unit rate for pricing code [%s]',
                    $code,
                ),
            );

            $unroundedProduct = bcmul(
                $quantity,
                $unitRate,
                self::PRODUCT_SCALE,
            );

            $ruleRoundedAmount = $this->roundHalfUp(
                $unroundedProduct,
                $roundingScale,
            );

            $lineAmount = $this->roundHalfUp(
                $ruleRoundedAmount,
                self::STORED_AMOUNT_SCALE,
            );

            $subtotalAmount = bcadd(
                $subtotalAmount,
                $lineAmount,
                self::STORED_AMOUNT_SCALE,
            );

            $descriptionAttribute =
                $item->getAttribute('description');

            $description = is_string($descriptionAttribute)
                ? $descriptionAttribute
                : null;

            $lines[] = new CalculatedPriceLine(
                priceListItemId: (int) $item->getKey(),
                pricingCode: $code,
                description: $description,
                quantity: $quantity,
                unit: $unit,
                unitRate: $unitRate,
                currency: $currency,
                lineAmount: $lineAmount,
                sourceField: $sourceField,
                roundingScale: $roundingScale,
                roundingMethod: $roundingMethod,
                position: $position,
            );

            $seenCodes[$code] = true;
            $seenPositions[$position] = true;
        }

        return new PricingCalculationResult(
            currency: $currency,
            inputSnapshot: $inputSnapshot,
            lines: $lines,
            subtotalAmount: $subtotalAmount,
            totalAmount: $subtotalAmount,
        );
    }

    /**
     * @param  array<string, mixed>  $inputSnapshot
     */
    private function quantityFromSnapshot(
        array $inputSnapshot,
        string $sourceField,
    ): string {
        if (! array_key_exists($sourceField, $inputSnapshot)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Input snapshot is missing quantity source [%s].',
                    $sourceField,
                ),
            );
        }

        return $this->normalizeDecimal(
            $inputSnapshot[$sourceField],
            self::QUANTITY_SCALE,
            sprintf(
                'Quantity source [%s]',
                $sourceField,
            ),
        );
    }

    private function normalizeDecimal(
        mixed $value,
        int $scale,
        string $label,
    ): string {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (
            ! is_string($value)
            || preg_match(
                '/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D',
                $value,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative decimal string or integer.',
                    $label,
                ),
            );
        }

        $parts = explode(
            '.',
            $value,
            2,
        );

        $decimalPlaces = isset($parts[1])
            ? strlen($parts[1])
            : 0;

        if ($decimalPlaces > $scale) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s supports at most %d decimal places.',
                    $label,
                    $scale,
                ),
            );
        }

        return bcadd(
            $value,
            '0',
            $scale,
        );
    }

    private function positiveInteger(
        mixed $value,
        string $label,
    ): int {
        $integer = $this->nonNegativeInteger(
            $value,
            $label,
        );

        if ($integer < 1) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be at least 1.',
                    $label,
                ),
            );
        }

        return $integer;
    }

    private function nonNegativeInteger(
        mixed $value,
        string $label,
    ): int {
        if (
            ! is_int($value)
            && ! (
                is_string($value)
                && preg_match('/^(?:0|[1-9][0-9]*)$/D', $value) === 1
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative integer.',
                    $label,
                ),
            );
        }

        $integer = (int) $value;

        if ($integer < 0) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-negative integer.',
                    $label,
                ),
            );
        }

        return $integer;
    }

    private function requiredString(
        mixed $value,
        string $label,
    ): string {
        if (
            ! is_string($value)
            || trim($value) === ''
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must be a non-empty string.',
                    $label,
                ),
            );
        }

        return $value;
    }

    private function roundHalfUp(
        string $value,
        int $scale,
    ): string {
        $increment = $scale === 0
            ? '0.5'
            : '0.'.str_repeat('0', $scale).'5';

        $adjusted = bcadd(
            $value,
            $increment,
            $scale + 1,
        );

        return bcadd(
            $adjusted,
            '0',
            $scale,
        );
    }
}
