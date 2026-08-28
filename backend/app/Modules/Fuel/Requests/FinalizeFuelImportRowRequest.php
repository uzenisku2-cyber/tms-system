<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FinalizeFuelImportRowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'expected_correction_revision' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
