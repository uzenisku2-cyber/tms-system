<?php

namespace App\Core\Observability;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Trace
{
    private static ?string $traceId = null;

    public static function id(): string
    {
        if (self::$traceId) {
            return self::$traceId;
        }

        self::$traceId =
            request()->header('X-Trace-ID')
            ?? (string) Str::uuid();

        return self::$traceId;
    }

    public static function log(string $type, array $data = []): void
    {
        DB::table('traces')->insert([
            'trace_id' => self::id(),
            'type' => $type,
            'payload' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}