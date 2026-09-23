<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexVehicleRegistryAdministrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicle.view') === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'lifecycle_status' => ['nullable', Rule::in(['active', 'temporarily_inactive', 'restricted', 'disposed', 'written_off', 'archived'])],
            'active' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
