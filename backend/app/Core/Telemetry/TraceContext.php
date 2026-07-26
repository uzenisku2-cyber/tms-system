<?php

declare(strict_types=1);

namespace App\Core\Telemetry;

use Illuminate\Support\Str;

class TraceContext
{
    private static ?string $traceId = null;

    /**
     * INIT TRACE FOR REQUEST
     */
    public static function init(?string $traceId = null): string
    {
        self::$traceId = $traceId ?? (string) Str::uuid();

        return self::$traceId;
    }

    /**
     * GET CURRENT TRACE ID
     */
    public static function get(): string
    {
        return self::$traceId ?? self::init();
    }
}