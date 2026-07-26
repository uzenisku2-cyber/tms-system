<?php

declare(strict_types=1);

namespace App\Core\Observability;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestLogger
{
    /**
     * Log API request (and optional response)
     */
    public static function log(Request $request, mixed $response = null): string
    {
        $traceId = (string) Str::uuid();

        Log::info('API REQUEST', [
            'trace_id' => $traceId,
            'method'   => $request->method(),
            'url'      => $request->fullUrl(),
            'user_id'  => optional($request->user())->id,
            'ip'       => $request->ip(),
            'payload'  => $request->all(),
        ]);

        if ($response !== null) {
            Log::info('API RESPONSE', [
                'trace_id' => $traceId,
                'status'   => method_exists($response, 'status') ? $response->status() : null,
            ]);
        }

        return $traceId;
    }
}