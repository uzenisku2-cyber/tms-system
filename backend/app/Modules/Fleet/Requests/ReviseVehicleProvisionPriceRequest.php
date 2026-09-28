<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Requests;

class ReviseVehicleProvisionPriceRequest extends StoreVehicleProvisionPriceRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + ['expected_price_revision' => ['required', 'integer', 'min:1']];
    }
}
