<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BindDriverQualityProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'profile_public_id' => [
                'required',
                'uuid',
            ],
            'valid_from' => [
                'required',
                'date_format:Y-m-d',
                'regex:/^\d{4}-\d{2}-01$/',
            ],
        ];
    }
}
