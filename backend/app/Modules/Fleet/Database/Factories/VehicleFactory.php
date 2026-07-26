<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Database\Factories;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => strtoupper(fake()->bothify('?? ###??')),
            'vin' => strtoupper(fake()->bothify('#################')),
            'manufacturer' => fake()->randomElement([
                'Volvo',
                'Scania',
                'Mercedes-Benz',
                'MAN',
                'DAF',
            ]),
            'model' => fake()->word(),
            'year' => fake()->numberBetween(2015, now()->year),
            'fuel_type' => fake()->randomElement([
                'diesel',
                'electric',
                'lng',
            ]),
            'mileage' => fake()->numberBetween(0, 900000),
            'active' => fake()->boolean(90),
        ];
    }
}
