<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notifications\Controllers\NotificationController;


Route::middleware('auth:sanctum')
    ->group(function () {


        Route::get(
            '/notifications',
            [
                NotificationController::class,
                'index'
            ]
        );


        Route::get(
            '/notifications/unread',
            [
                NotificationController::class,
                'unread'
            ]
        );


        Route::patch(
            '/notifications/{id}/read',
            [
                NotificationController::class,
                'read'
            ]
        );


        Route::patch(
            '/notifications/read-all',
            [
                NotificationController::class,
                'readAll'
            ]
        );


    });