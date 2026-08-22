<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateDepotImportDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $alias = $this->input('carrier_alias');

        if (is_string($alias)) {
            $this->merge([
                'carrier_alias' => trim($alias),
            ]);
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'workbook' => [
                'required',
                'file',
                'max:32768',
            ],
            'carrier_alias' => [
                'required',
                'string',
                'max:255',
            ],
            'carrier_alias_confirmed' => [
                'required',
                'accepted',
            ],
        ];
    }
}
