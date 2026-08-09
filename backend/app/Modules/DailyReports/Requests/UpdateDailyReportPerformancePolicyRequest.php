<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDailyReportPerformancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $percentage = [
            'present',
            'nullable',
            'numeric',
            'decimal:0,2',
            'between:0,100',
        ];

        return [
            'redirected_max_percent' =>
                $percentage,
            'kilometre_deviation_max_percent' =>
                $percentage,
            'delivered_address_min_percent' =>
                $percentage,
            'rejected_max_percent' =>
                $percentage,
            'not_delivered_max_percent' =>
                $percentage,
        ];
    }
}