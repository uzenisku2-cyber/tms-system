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
            'windows' => ['sometimes', 'array', 'min:1', 'max:8'],
            'windows.*.start' => ['required_with:windows', 'date_format:H:i'],
            'windows.*.end' => ['required_with:windows', 'regex:/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$|^24:00$/'],
        ];
    }
}
