<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;

final class FuelCardAssignment extends Model
{
    protected $fillable = ['public_id', 'fuel_card_id', 'responsible_organization_id', 'driver_id', 'vehicle_id', 'assignment_type', 'status', 'valid_from', 'valid_until', 'usage_restrictions', 'reason', 'assigned_by_user_id', 'ended_by_user_id'];

    protected $casts = ['valid_from' => 'immutable_datetime', 'valid_until' => 'immutable_datetime'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
