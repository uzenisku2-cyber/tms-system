<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Modules\Fleet\Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $registration_number
 * @property string $vin
 * @property string $manufacturer
 * @property string $model
 * @property int|null $year
 * @property string|null $fuel_type
 * @property int $mileage
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static VehicleFactory factory($count = null, $state = [])
 */
class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }
}
