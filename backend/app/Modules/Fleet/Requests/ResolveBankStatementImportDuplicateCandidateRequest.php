<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResolveBankStatementImportDuplicateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('compensation.manage') ?? false;
    }

    public function rules(): array
    {
        return ['idempotency_key' => ['required', 'uuid'], 'decision' => ['required', Rule::in(['confirmed_duplicate', 'dismissed'])], 'reason' => ['required', 'string', 'max:1000']];
    }
}
