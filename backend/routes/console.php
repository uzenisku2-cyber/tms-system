<?php

use Illuminate\Support\Facades\Schedule;


/*
|--------------------------------------------------------------------------
| Console Scheduler
|--------------------------------------------------------------------------
*/


Schedule::command('trips:monitor')
    ->everyMinute();