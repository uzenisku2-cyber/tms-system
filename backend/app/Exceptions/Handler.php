<?php

declare(strict_types=1);

namespace App\Exceptions;


use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;



class Handler extends ExceptionHandler
{


    public function render(
        $request,
        Throwable $e
    ) {


        if ($request->expectsJson()) {


            return response()->json([


                'ok' => false,

                'error' => true,

                'message' => $e->getMessage(),

                'type' => get_class($e),

                'file' => $e->getFile(),

                'line' => $e->getLine(),


            ], 500);


        }



        return parent::render(

            $request,

            $e

        );


    }


}