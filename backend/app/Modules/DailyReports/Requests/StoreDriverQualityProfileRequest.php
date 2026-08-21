<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDriverQualityProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $name = $this->input('name');

        $this->merge([
            'code' => is_string($code)
                ? mb_strtoupper(trim($code), 'UTF-8')
                : $code,
            'name' => is_string($name)
                ? trim($name)
                : $name,
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'calculation_method' => [
                'required',
                'string',
                Rule::in(
                    DriverQualityProfileVersion::CALCULATION_METHODS,
                ),
            ],
            'numerator_sources' => [
                'present',
                Rule::requiredIf(
                    fn (): bool => $this->input('calculation_method')
                        === DriverQualityProfileVersion::METHOD_PROCESSED_SHARE,
                ),
                'array',
                'max:3',
                'prohibited_if:calculation_method,'.DriverQualityProfileVersion::METHOD_DISABLED,
            ],
            'numerator_sources.*' => [
                'string',
                'distinct',
                Rule::in(
                    DriverQualityProfileComponent::SOURCES,
                ),
            ],
            'change_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
