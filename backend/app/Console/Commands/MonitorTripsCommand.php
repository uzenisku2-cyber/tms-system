<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;


use App\Modules\Trips\Models\Trip;
use App\Services\TripMonitoringService;



#[Signature('trips:monitor')]

#[Description('Monitor active trips and create operational alerts')]

class MonitorTripsCommand extends Command
{


    public function __construct(
        protected TripMonitoringService $monitor
    ) {

        parent::__construct();

    }





    public function handle()
    {


        /*
        |--------------------------------------------------------------------------
        | Monitor started and finished trips
        |--------------------------------------------------------------------------
        |
        | Started trips:
        | - GPS lost
        | - ETA delay
        | - vehicle idle
        |
        | Finished trips:
        | - resolve remaining operational alerts
        |
        */


        $trips = Trip::whereIn(

            'status',

            [

                Trip::STATUS_STARTED,

                Trip::STATUS_FINISHED,

            ]

        )

        ->get();





        foreach ($trips as $trip) {


            $this->monitor->checkEtaDelay(

                $trip

            );



            /*
            | Only active trips need live checks
            */

            if (
                $trip->status === Trip::STATUS_STARTED
            ) {


                $this->monitor->checkGpsLost(

                    $trip

                );



                $this->monitor->checkVehicleIdle(

                    $trip

                );


            }


        }





        $this->info(

            "Checked {$trips->count()} trips"

        );



        return Command::SUCCESS;


    }


}