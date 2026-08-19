<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\PriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PriceListIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $currency = $this->input('currency');

        if (is_string($currency)) {
            $this->merge([
                'currency' => mb_strtoupper(
                    trim($currency),
                    'UTF-8',
                ),
            ]);
        }

        $perspective = $this->input('perspective');

        if (is_string($perspective)) {
            $this->merge([
                'perspective' => mb_strtolower(
                    trim($perspective),
                    'UTF-8',
                ),
            ]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'string',
                Rule::in(PriceList::STATUSES),
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],
            'perspective' => [
                'nullable',
                'string',
                Rule::in(PriceList::PERSPECTIVES),
            ],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'name',
                    'status',
                    'currency',
                    'current_version',
                    'created_at',
                    'updated_at',
                ]),
            ],
            'sort_dir' => [
                'nullable',
                'string',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],
        ];
    }
}
