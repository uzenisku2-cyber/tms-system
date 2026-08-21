<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use App\Modules\DailyReports\Models\DriverQualityProfileComponent;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDriverQualityProfileVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'lock_version' => [
                'required',
                'integer',
                'min:1',
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
