<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\DriverPriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DriverPriceListIndexRequest extends FormRequest
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
                Rule::in(DriverPriceList::STATUSES),
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],
            'driver_organization_assignment_id' => [
                'nullable',
                'integer',
                'min:1',
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
