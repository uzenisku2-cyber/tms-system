<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{

    /**
     * Standard success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'OK',
        int $code = 200
    ): JsonResponse {

        return ApiResponse::success(
            $data,
            $message,
            $code
        );

    }



    /**
     * Standard error response
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        mixed $errors = null
    ): JsonResponse {

        return ApiResponse::error(
            $message,
            $code,
            $errors
        );

    }



    /**
     * Standard paginated response
     */
    protected function paginated(
        mixed $paginator,
        string $message = 'OK'
    ): JsonResponse {

        return ApiResponse::paginated(
            $paginator,
            $message
        );

    }

}