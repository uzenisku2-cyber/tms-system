<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Core\Observability\RequestLogger;

class ApiObservabilityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        // PROCESS REQUEST
        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        // LOG FULL TRACE
        RequestLogger::log($request, $response);

        logger()->info('API PERFORMANCE', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}