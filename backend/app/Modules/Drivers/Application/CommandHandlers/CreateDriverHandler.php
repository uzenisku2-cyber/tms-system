<?php

namespace App\Modules\Drivers\Application\CommandHandlers;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Domain\Events\DriverCreated;

class CreateDriverHandler
{
    public function handle($command): Driver
    {
        $driver = Driver::create([

            'user_id' => $command->userId,

            'first_name' =>
                $command->data['first_name'],

            'last_name' =>
                $command->data['last_name'],

            'phone' =>
                $command->data['phone'] ?? null,

            'email' =>
                $command->data['email'] ?? null,

            'license_number' =>
                $command->data['license_number'],

            'license_category' =>
                $command->data['license_category'] ?? null,

            'active' => true,

        ]);


        event(new DriverCreated(
            driverId: $driver->id,
            payload: $driver->toArray()
        ));


        return $driver;
    }
}