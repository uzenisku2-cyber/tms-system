<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class RecordDailyReportCorrectionRequest extends FormRequest
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
        return DailyReportRequestRules::mutation();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (
                DailyReportRequestRules::MUTABLE_FIELDS as $field
            ) {
                if ($this->exists($field)) {
                    return;
                }
            }

            $validator->errors()->add(
                'attributes',
                'At least one corrected daily report field must be provided.',
            );
        });
    }
}
