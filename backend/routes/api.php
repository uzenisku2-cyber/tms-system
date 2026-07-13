<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 GATEWAY
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    foreach (glob(app_path('Modules/*/Routes/api.php')) as $file) {
        require $file;
    }

});