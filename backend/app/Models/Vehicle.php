<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Vehicle extends Model
{

    protected $fillable = [
        'registration_number',
        'vin',
        'manufacturer',
        'model',
        'year',
        'fuel_type',
        'mileage',
        'active',
    ];



    public function positions(): HasMany
    {
        return $this->hasMany(
            VehiclePosition::class,
            'vehicle_id'
        );
    }

}