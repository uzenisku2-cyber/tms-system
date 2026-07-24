<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Models\VehiclePosition;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $registration_number
 * @property string $vin
 * @property string $manufacturer
 * @property string $model
 * @property int|null $year
 * @property string|null $vehicle_type
 * @property string|null $vehicle_size
 * @property string|null $color
 * @property string|null $icon
 * @property string|null $manufacturer_logo
 * @property string|null $body_style
 * @property string|null $fuel_type
 * @property int $mileage
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Trip> $trips
 * @property-read Collection<int, VehiclePosition> $positions
 * @property-read VehiclePosition|null $latestPosition
 * @property-read string $label
 */
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(
            Trip::class,
            'vehicle_id'
        );
    }

    /**
     * @return HasMany<VehiclePosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(
            VehiclePosition::class,
            'vehicle_id'
        );
    }

    /**
     * @return HasOne<VehiclePosition, $this>
     */
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
        return ! $this->hasActiveTrip();
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
