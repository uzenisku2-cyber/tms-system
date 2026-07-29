<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DailyReportTransitionRequest extends FormRequest
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
        return DailyReportRequestRules::transition();
    }
}
