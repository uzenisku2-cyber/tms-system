<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclePosition extends Model
{
    protected $fillable = [
        'vehicle_id',
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
    ];


    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'integer',
        'heading' => 'integer',
    ];


    public function vehicle()
    {
        return $this->belongsTo(
            Vehicle::class
        );
    }
}