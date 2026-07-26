<?php

declare(strict_types=1);

namespace App\Core\EventBus;

use App\Core\Events\EventEnvelope;
use App\Core\EventStore\EventStore;
use App\Core\EventStreaming\EventPublisher;
use App\Core\Organizations\OrganizationContext;
use App\Core\Telemetry\TraceContext;

class EventBus
{
    /**
     * ENTERPRISE EVENT DISPATCHER
     * (SYNC + STREAM + STORE + TRACE)
     */
    public static function dispatch(object $event): void
    {
        // 1. TRACE CONTEXT (distributed tracing)
        $traceId = TraceContext::get();

        // 2. TENANT CONTEXT (multi-tenant SaaS ready)
        $organizationId = app(OrganizationContext::class)->id();

        // 3. BUILD STANDARDIZED EVENT ENVELOPE
        $envelope = EventEnvelope::wrap(
            event: $event,
            traceId: $traceId,
            tenantId: $organizationId === null ? null : (string) $organizationId
        );

        // 4. EVENT STORE (source of truth / replay / audit)
        EventStore::record($envelope);

        // 5. STREAM PUBLISH (Kafka-ready abstraction layer)
        EventPublisher::publish($envelope);
    }
}
