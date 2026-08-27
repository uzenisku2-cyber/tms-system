<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreFuelImportRowCorrectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'corrected_payload' => ['required', 'array', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
