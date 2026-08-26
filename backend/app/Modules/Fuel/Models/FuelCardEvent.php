<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;

final class FuelCardEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['fuel_card_id', 'fuel_card_assignment_id', 'fuel_card_settlement_policy_id', 'organization_id', 'event_type', 'acted_by_user_id', 'reason', 'before_payload', 'after_payload', 'created_at'];

    protected $casts = ['before_payload' => 'array', 'after_payload' => 'array', 'created_at' => 'immutable_datetime'];
}
