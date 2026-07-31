<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\PriceListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePriceListVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        $changeReason = $this->input('change_reason');

        if (is_string($changeReason)) {
            $changeReason = trim($changeReason);

            $normalized['change_reason'] = (
                $changeReason === ''
            )
                ? null
                : $changeReason;
        }

        $items = $this->input('items');

        if (is_array($items)) {
            $normalizedItems = [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    $normalizedItems[] = $item;

                    continue;
                }

                $normalizedItem = $item;

                $code = $item['code'] ?? null;

                if (is_string($code)) {
                    $normalizedItem['code'] = trim($code);
                }

                $description = $item['description'] ?? null;

                if (is_string($description)) {
                    $description = trim($description);

                    $normalizedItem['description'] = (
                        $description === ''
                    )
                        ? null
                        : $description;
                }

                $unitRate = $item['unit_rate'] ?? null;

                if (is_string($unitRate)) {
                    $normalizedItem['unit_rate'] = trim(
                        $unitRate,
                    );
                }

                $normalizedItems[] = $normalizedItem;
            }

            $normalized['items'] = $normalizedItems;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'valid_from' => [
                'nullable',
                'required_with:valid_until',
                'date_format:Y-m-d',
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'change_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'items' => [
                'required',
                'array',
                'size:'.count(PriceListItem::CODES),
            ],
            'items.*' => [
                'required',
                'array:code,description,unit_rate',
            ],
            'items.*.code' => [
                'required',
                'string',
                Rule::in(PriceListItem::CODES),
                'distinct:strict',
            ],
            'items.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.unit_rate' => [
                'required',
                'numeric',
                'decimal:0,4',
                'between:0,9999999999.9999',
            ],
        ];
    }
}
