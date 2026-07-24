<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(
    basePath: dirname(__DIR__)
)


    ->withRouting(

        web: __DIR__ . '/../routes/web.php',

        api: __DIR__ . '/../routes/api.php',

        commands: __DIR__ . '/../routes/console.php',

        health: '/up',

    )


    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        [
            'middleware' => [
                'api',
                'auth:sanctum',
            ],
        ]
    )
    ->withMiddleware(function (Middleware $middleware) {


        $middleware->alias([

            'perm' => \App\Http\Middleware\CheckPermission::class,

        ]);


    })


    ->withExceptions(function (Exceptions $exceptions) {


        /*
        |--------------------------------------------------------------------------
        | Validation errors (422)
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (
            ValidationException $e,
            $request
        ) {


            return response()->json([

                'error' => true,

                'message' => 'Validation failed',

                'errors' => $e->errors(),

                'type' => get_class($e),

            ], 422);


        });





        /*
        |--------------------------------------------------------------------------
        | Authentication errors (401)
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (
            AuthenticationException $e,
            $request
        ) {


            return response()->json([

                'error' => true,

                'message' => 'Unauthenticated.',

                'type' => get_class($e),

            ], 401);


        });





        /*
        |--------------------------------------------------------------------------
        | Route not found (404)
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (
            NotFoundHttpException $e,
            $request
        ) {


            return response()->json([

                'error' => true,

                'message' => 'Resource not found.',

                'type' => get_class($e),

            ], 404);


        });





        /*
        |--------------------------------------------------------------------------
        | HTTP exceptions (403, 405, ...)
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (
            HttpExceptionInterface $e,
            $request
        ) {


            return response()->json([

                'error' => true,

                'message' => $e->getMessage(),

                'type' => get_class($e),

            ], $e->getStatusCode());


        });





        /*
        |--------------------------------------------------------------------------
        | Generic API exception fallback (500)
        |--------------------------------------------------------------------------
        */


        $exceptions->render(function (
            \Throwable $e,
            $request
        ) {


            return response()->json([

                'error' => true,

                'message' => $e->getMessage(),

                'type' => get_class($e),

            ], 500);


        });


    })


    ->create();