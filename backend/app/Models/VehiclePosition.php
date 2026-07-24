<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $vehicle_id
 * @property int $trip_id
 * @property float $latitude
 * @property float $longitude
 * @property int|null $speed
 * @property int|null $heading
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vehicle|null $vehicle
 */
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

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            Vehicle::class
        );
    }
}
