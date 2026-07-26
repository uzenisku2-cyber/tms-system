<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\EventStreaming\Contracts\RealtimeTransport;
use App\Core\EventStreaming\Transports\ReverbRealtimeTransport;
use App\Core\Events\EventEnvelope;
use App\Models\TripLocation;
use App\Modules\Trips\Domain\Listeners\UpdateTripRealtimeProjection;
use App\Observers\TripLocationObserver;
use App\Support\Generator\Generator;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {


        $this->app->singleton(
            Generator::class
        );



        $this->app->bind(

            RealtimeTransport::class,

            ReverbRealtimeTransport::class

        );


    }




    public function boot(): void
    {


        Authenticate::redirectUsing(
            fn () => null
        );



        TripLocation::observe(
            TripLocationObserver::class
        );



        Event::listen(

            EventEnvelope::class,

            UpdateTripRealtimeProjection::class

        );


    }


}