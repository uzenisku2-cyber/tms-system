<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\FinancialCalculation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FinancialCalculationIndexRequest extends FormRequest
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

        $perPage = $this->input('per_page');

        if (
            is_string($perPage)
            && preg_match('/^-?[0-9]+$/', $perPage) === 1
        ) {
            $this->merge([
                'per_page' => (int) $perPage,
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
                Rule::in(FinancialCalculation::STATUSES),
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],
            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'calculated_at',
                    'status',
                    'currency',
                    'total_amount',
                    'created_at',
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
