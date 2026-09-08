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
            'adapter' => ['sometimes', Rule::in(['configurable_csv', 'csob_csv'])],
            'file' => ['required', 'file', 'max:20480', 'mimes:csv,txt'],
            'delimiter' => ['required_unless:adapter,csob_csv', Rule::in([',', ';', "\t"])],
            'encoding' => ['required_unless:adapter,csob_csv', Rule::in(['UTF-8', 'WINDOWS-1250', 'ISO-8859-2'])],
            'mapping_version' => ['required_unless:adapter,csob_csv', 'string', 'max:64'],
            'date_format' => ['required_unless:adapter,csob_csv', 'string', 'max:32'],
            'decimal_separator' => ['required_unless:adapter,csob_csv', Rule::in(['.', ','])],
            'default_account_identifier' => ['nullable', 'string', 'max:191'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'mapping' => ['required_unless:adapter,csob_csv', 'array'],
            'mapping.booked_at' => ['required_unless:adapter,csob_csv', 'string', 'max:191'],
            'mapping.amount' => ['required_unless:adapter,csob_csv', 'string', 'max:191'],
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
