<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;

final class FuelCardSettlementPolicy extends Model
{
    protected $fillable = ['public_id', 'owner_organization_id', 'fuel_card_id', 'settlement_target', 'discount_beneficiary', 'amount_basis', 'vat_mode', 'valid_from', 'valid_until', 'reason', 'created_by_user_id'];

    protected $casts = ['valid_from' => 'date', 'valid_until' => 'date'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
