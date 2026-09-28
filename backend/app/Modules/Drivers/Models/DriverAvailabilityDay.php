<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use Illuminate\Database\Eloquent\Model;

final class DriverAvailabilityDay extends Model
{
    protected $fillable = [
        'organization_id', 'driver_id', 'date', 'availability', 'decision',
        'timezone', 'reason', 'revision', 'submitted_by_user_id',
        'decided_by_user_id', 'decided_at',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'decided_at' => 'datetime'];
    }
}
