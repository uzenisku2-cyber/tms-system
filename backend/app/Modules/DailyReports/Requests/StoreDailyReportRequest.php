<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return DailyReportRequestRules::creation();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actualKmProvided =
                $this->input('actual_km') !== null;

            $sourceProvided =
                $this->input('actual_km_source') !== null;

            if ($actualKmProvided === $sourceProvided) {
                return;
            }

            $validator->errors()->add(
                'actual_km',
                'Actual kilometres and their source must be provided together.',
            );

            $validator->errors()->add(
                'actual_km_source',
                'Actual kilometres and their source must be provided together.',
            );
        });
    }
}
