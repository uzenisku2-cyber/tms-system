<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexFuelTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'provider' => ['nullable', Rule::in(['ORLEN', 'MOL'])],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'card' => ['nullable', 'string', 'max:128'],
            'search' => ['nullable', 'string', 'max:100'],
            'reconciliation_status' => ['nullable', Rule::in(['pending', 'matched', 'review_required', 'resolved'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50, 100])],
        ];
    }
}
