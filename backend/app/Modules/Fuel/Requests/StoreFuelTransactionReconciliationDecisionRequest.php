<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFuelTransactionReconciliationDecisionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'expected_revision' => ['required', 'integer', 'min:1'],
            'decision_code' => ['required', 'string', Rule::in(['confirm_driver_day', 'select_daily_report', 'accept_without_operational_activity', 'return_to_review'])],
            'daily_report_id' => ['nullable', 'integer', 'min:1', 'exists:daily_reports,id', 'required_if:decision_code,select_daily_report'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
