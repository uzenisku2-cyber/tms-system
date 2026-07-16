<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Events\TripRealtimeBroadcast;
use App\Models\VehiclePosition;



class SimulateGps extends Command
{


    protected $signature =
        'gps:simulate {trip=6}';


    protected $description =
        'Simulate vehicle GPS movement';



    public function handle()
    {


        $trip =
            $this->argument('trip');



        $route = [

            [
                50.170,
                14.560
            ],

            [
                50.171,
                14.561
            ],

            [
                50.172,
                14.563
            ],

            [
                50.174,
                14.566
            ],

            [
                50.176,
                14.569
            ],

            [
                50.178,
                14.572
            ],

        ];



        foreach($route as $point)
        {


            $latitude =
                $point[0];


            $longitude =
                $point[1];



            $speed =
                rand(50,90);



            $heading = match(true) {

    $longitude < 14.563 => 90,

    $longitude < 14.569 => 95,

    default => 100,
};



            /*
             * ulozeni GPS
             */

            VehiclePosition::create([

    'trip_id' => $trip,

    'vehicle_id' => 4,

    'latitude' => $latitude,

    'longitude' => $longitude,

    'speed' => $speed,

    'heading' => $heading,


            ]);




            /*
             * realtime broadcast
             */

            event(
                new TripRealtimeBroadcast(
                    'trip.'.$trip,
                    [

                    'trip_id'=>$trip,

                    'latitude'=>$latitude,

                    'longitude'=>$longitude,

                    'speed'=>$speed,

                    'heading'=>$heading

                    ]
                )
            );



            $this->info(
                "GPS {$latitude}, {$longitude}"
            );



            sleep(2);


        }



        return Command::SUCCESS;

    }

}