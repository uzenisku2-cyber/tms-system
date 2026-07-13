<?php

namespace App\Providers;

use App\Support\Generator\Generator;
use App\Models\TripLocation;
use App\Observers\TripLocationObserver;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\Authenticate;


class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {

        $this->app->singleton(
            Generator::class
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

    }


}