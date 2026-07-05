<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

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
 */
class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $guarded = [];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'active' => 'boolean',
    ];
}
