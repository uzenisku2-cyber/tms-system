<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Models\VehiclePosition;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'registration_number',
        'vin',
        'manufacturer',
        'model',
        'year',
        'vehicle_type',
        'vehicle_size',
        'color',
        'icon',
        'manufacturer_logo',
        'body_style',
        'fuel_type',
        'mileage',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'year' => 'integer',
        'mileage' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(
            Trip::class,
            'vehicle_id'
        );
    }

    public function positions(): HasMany
    {
        return $this->hasMany(
            VehiclePosition::class,
            'vehicle_id'
        );
    }

    public function latestPosition(): HasOne
    {
        return $this->hasOne(
            VehiclePosition::class,
            'vehicle_id'
        )->latestOfMany();
    }

    public function hasActiveTrip(): bool
    {
        return $this
            ->trips()
            ->whereIn(
                'status',
                [
                    Trip::STATUS_ASSIGNED,
                    Trip::STATUS_STARTED,
                ]
            )
            ->exists();
    }

    public function isAvailable(): bool
    {
        return !$this->hasActiveTrip();
    }

    public function getLabelAttribute(): string
    {
        return trim(
            $this->manufacturer
            .' '
            .$this->model
            .' ('
            .$this->registration_number
            .')'
        );
    }
}
