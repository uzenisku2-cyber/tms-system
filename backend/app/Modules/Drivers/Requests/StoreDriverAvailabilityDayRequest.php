<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDriverAvailabilityDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'availability' => ['required', 'in:available,unavailable'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'expected_revision' => ['required', 'integer', 'min:0'],
        ];
    }
}
