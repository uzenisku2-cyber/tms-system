<?php

declare(strict_types=1);

namespace App\Core\EventStore;

use App\Core\Events\EventEnvelope;
use Illuminate\Support\Facades\Log;

class EventStore
{
    /**
     * PERSIST EVENTS (future: DB / Kafka / Elastic / S3)
     */
    public static function record(EventEnvelope $event): void
    {
        Log::info('EVENT_STORED', [
            'event_id' => $event->eventId,
            'type' => $event->eventType,
            'trace_id' => $event->traceId,
            'tenant_id' => $event->tenantId,
            'payload' => $event->payload,
            'occurred_at' => $event->occurredAt->format(DATE_ATOM),
        ]);
    }
}