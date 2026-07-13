<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use App\Models\User;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [

        'user_id',

        'first_name',

        'last_name',

        'phone',

        'email',

        'license_number',

        'license_category',

        'active',

    ];


    protected $casts = [

        'active' => 'boolean',

    ];


    /**
     * Uživatel, kterému řidič patří.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    /**
     * Jízdy tohoto řidiče.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(
            Trip::class,
            'driver_id'
        );
    }


    /**
     * Má řidič právě aktivní jízdu?
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
     * Celé jméno řidiče.
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name . ' ' . $this->last_name
        );
    }
}