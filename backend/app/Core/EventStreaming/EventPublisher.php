<?php

declare(strict_types=1);

namespace App\Core\EventStreaming;

use App\Core\Events\EventEnvelope;
use App\Core\EventStore\EventStore;
use Illuminate\Support\Facades\Log;


class EventPublisher
{


    public static function publish(
        EventEnvelope $event
    ): void {


        // 1. STORE EVENT (SOURCE OF TRUTH)

        EventStore::record(
            $event
        );



        // 2. STREAM EVENT (future Kafka / queues)

        Log::info(
            'EVENT_PUBLISHED_TO_STREAM',
            [

                'event_id' => $event->eventId,

                'type' => $event->eventType,

                'trace_id' => $event->traceId,

                'tenant_id' => $event->tenantId,

            ]
        );



        // 3. LOCAL DISPATCH (Laravel fallback)

        event($event);


    }


}