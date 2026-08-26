<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFuelCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['ORLEN', 'MOL', 'OTHER'])],
            'provider_card_identifier' => ['required', 'string', 'max:128'],
            'masked_card_number' => ['required', 'string', 'max:64', 'not_regex:/^[0-9]{10,}$/'],
            'label' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date_format:Y-m-d'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'purchase_restrictions' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
