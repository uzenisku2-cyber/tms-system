<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelCard extends Model
{
    protected $fillable = ['public_id', 'owner_organization_id', 'provider', 'provider_card_identifier', 'masked_card_number', 'label', 'status', 'valid_from', 'expires_at', 'currency', 'purchase_restrictions', 'lock_version', 'created_by_user_id'];

    protected $casts = ['valid_from' => 'date', 'expires_at' => 'date', 'purchase_restrictions' => 'array', 'lock_version' => 'integer'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FuelCardAssignment::class);
    }

    public function settlementPolicies(): HasMany
    {
        return $this->hasMany(FuelCardSettlementPolicy::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FuelCardEvent::class);
    }
}
