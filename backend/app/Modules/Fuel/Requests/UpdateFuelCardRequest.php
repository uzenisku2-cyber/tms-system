<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFuelCardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'masked_card_number' => ['required', 'string', 'max:64', 'regex:/[^0-9]/'],
            'expires_at' => ['nullable', 'date'],
            'purchase_restrictions' => ['nullable', 'array'],
            'provider_status' => ['required', Rule::in([
                'active',
                'temporarily_blocked',
                'blocked',
                'cancelled',
                'unknown',
                'verification_required',
            ])],
            'provider_status_verified_at' => ['nullable', 'date'],
            'provider_status_note' => ['nullable', 'string', 'max:2000'],
            'lock_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
