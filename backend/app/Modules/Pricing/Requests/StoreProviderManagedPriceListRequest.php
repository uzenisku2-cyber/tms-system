<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\PriceListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProviderManagedPriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (
            [
                'name',
                'description',
                'change_reason',
            ] as $field
        ) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            $normalized[$field] = (
                $field !== 'name'
                && $value === ''
            )
                ? null
                : $value;
        }

        $currency = $this->input('currency');

        if (is_string($currency)) {
            $normalized['currency'] = mb_strtoupper(
                trim($currency),
                'UTF-8',
            );
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

                $description =
                    $item['description'] ?? null;

                if (is_string($description)) {
                    $description = trim($description);

                    $normalizedItem['description'] = (
                        $description === ''
                    )
                        ? null
                        : $description;
                }

                $unitRate =
                    $item['unit_rate'] ?? null;

                if (is_string($unitRate)) {
                    $normalizedItem['unit_rate'] =
                        trim($unitRate);
                }

                $normalizedItems[] =
                    $normalizedItem;
            }

            $normalized['items'] =
                $normalizedItems;
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
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
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
