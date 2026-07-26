<?php

declare(strict_types=1);

namespace App\Core\Events;

class EventEnvelope
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public array $payload,
        public string $traceId,
        public string $tenantId,
        public \DateTimeImmutable $occurredAt
    ) {}

    public static function wrap(object $event, string $traceId, ?string $tenantId = null): self
    {
        return new self(
            eventId: uniqid('evt_', true),
            eventType: get_class($event),
            payload: method_exists($event, 'toArray')
                ? $event->toArray()
                : get_object_vars($event),
            traceId: $traceId,
            tenantId: $tenantId ?? 'system',
            occurredAt: new \DateTimeImmutable()
        );
    }
}