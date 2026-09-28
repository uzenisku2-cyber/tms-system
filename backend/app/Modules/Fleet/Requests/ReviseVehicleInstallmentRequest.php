<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

class ReviseVehicleInstallmentRequest extends StoreVehicleInstallmentRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + ['expected_installment_revision' => ['required', 'integer', 'min:1']];
    }
}
