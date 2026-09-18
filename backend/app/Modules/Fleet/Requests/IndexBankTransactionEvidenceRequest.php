<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IndexBankTransactionEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.view') === true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'direction' => ['nullable', 'in:credit,debit'],
            'currency' => ['nullable', 'string', 'size:3'],
            'booked_from' => ['nullable', 'date_format:Y-m-d'],
            'booked_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:booked_from'],
            'reference' => ['nullable', 'string', 'max:191'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'in:available,partially_allocated,fully_allocated,over_allocated'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
