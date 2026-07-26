<?php

declare(strict_types=1);

namespace App\Console\Commands;


use Illuminate\Console\Command;

use App\Modules\Trips\Models\Trip;

use App\Services\TripMonitoringService;



class MonitorTrips extends Command
{


    protected $signature = 'trips:monitor';



    protected $description = 'Monitor active trips for GPS, ETA and idle alerts';




    protected TripMonitoringService $monitoring;




    public function __construct(
        TripMonitoringService $monitoring
    ) {

        parent::__construct();

        $this->monitoring = $monitoring;

    }







    public function handle(): int
    {


        $trips = Trip::where(
                'status',
                Trip::STATUS_STARTED
            )
            ->get();





        if ($trips->isEmpty()) {


            $this->info(
                'No active trips found.'
            );


            return self::SUCCESS;

        }







        foreach ($trips as $trip) {


            $this->monitoring->checkGpsLost(
                $trip
            );



            $this->monitoring->checkVehicleIdle(
                $trip
            );



            $this->monitoring->checkEtaDelay(
                $trip
            );




            $this->line(

                "Checked trip #{$trip->id}"

            );


        }







        $this->info(

            "Monitoring completed. Trips checked: {$trips->count()}"

        );



        return self::SUCCESS;


    }


}