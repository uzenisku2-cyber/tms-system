<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePriceListRequest extends FormRequest
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
            'organization_relationship_id' => [
                'required',
                'integer',
                'min:1',
            ],
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
        ];
    }
}
