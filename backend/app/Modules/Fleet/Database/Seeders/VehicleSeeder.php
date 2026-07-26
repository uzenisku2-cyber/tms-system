<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Database\Seeders;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::factory()
            ->count(25)
            ->create();
    }
}
