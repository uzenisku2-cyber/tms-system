<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [

        'user_id',

        'registration_number',

        'vin',

        'manufacturer',

        'model',

        'year',

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
     * Uživatel, kterému vozidlo patří.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    /**
     * Jízdy tohoto vozidla.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(
            Trip::class,
            'vehicle_id'
        );
    }


    /**
     * Má vozidlo aktivní jízdu?
     */
    public function hasActiveTrip(): bool
    {
        return $this->trips()

            ->whereIn(
                'status',
                [
                    Trip::STATUS_ASSIGNED,
                    Trip::STATUS_STARTED,
                ]
            )

            ->exists();
    }


    /**
     * Je vozidlo dostupné?
     */
    public function isAvailable(): bool
    {
        return !$this->hasActiveTrip();
    }


    /**
     * Označení vozidla.
     */
    public function getLabelAttribute(): string
    {
        return trim(
            $this->manufacturer . ' ' . $this->model
            . ' (' . $this->registration_number . ')'
        );
    }
}