<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePriceListVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $changeReason = $this->input('change_reason');

        if (! is_string($changeReason)) {
            return;
        }

        $changeReason = trim($changeReason);

        $this->merge([
            'change_reason' => $changeReason === ''
                ? null
                : $changeReason,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_current_version' => [
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
        ];
    }
}
