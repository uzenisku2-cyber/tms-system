<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBankStatementImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'file' => ['required', 'file', 'max:20480', 'mimes:csv,txt'],
            'delimiter' => ['required', Rule::in([',', ';', "\t"])],
            'encoding' => ['required', Rule::in(['UTF-8', 'WINDOWS-1250', 'ISO-8859-2'])],
            'mapping_version' => ['required', 'string', 'max:64'],
            'date_format' => ['required', 'string', 'max:32'],
            'decimal_separator' => ['required', Rule::in(['.', ','])],
            'default_account_identifier' => ['nullable', 'string', 'max:191'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'mapping' => ['required', 'array'],
            'mapping.booked_at' => ['required', 'string', 'max:191'],
            'mapping.amount' => ['required', 'string', 'max:191'],
            'mapping.currency' => ['nullable', 'string', 'max:191'],
            'mapping.direction' => ['nullable', 'string', 'max:191'],
            'mapping.source_reference' => ['nullable', 'string', 'max:191'],
            'mapping.bank_statement_reference' => ['nullable', 'string', 'max:191'],
            'mapping.value_date' => ['nullable', 'string', 'max:191'],
            'mapping.account_identifier' => ['nullable', 'string', 'max:191'],
            'mapping.counterparty_name' => ['nullable', 'string', 'max:191'],
            'mapping.counterparty_account_identifier' => ['nullable', 'string', 'max:191'],
            'mapping.variable_symbol' => ['nullable', 'string', 'max:191'],
            'mapping.message' => ['nullable', 'string', 'max:191'],
        ];
    }
}
