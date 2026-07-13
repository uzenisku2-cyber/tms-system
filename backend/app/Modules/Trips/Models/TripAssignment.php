<?php

declare(strict_types=1);

namespace App\Modules\Trips\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAssignment extends Model
{
    protected $table = 'trip_assignments';


    protected $fillable = [

        'trip_id',

        'driver_id',

        'vehicle_id',

        'assigned_by',

        'assigned_at',

    ];


    protected $casts = [

        'assigned_at' => 'datetime',

    ];


    public function trip(): BelongsTo
    {
        return $this->belongsTo(
            Trip::class,
            'trip_id'
        );
    }


    public function driver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'driver_id'
        );
    }


    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            Vehicle::class,
            'vehicle_id'
        );
    }


    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }
}