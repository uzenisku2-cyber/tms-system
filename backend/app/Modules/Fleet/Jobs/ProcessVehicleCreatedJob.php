<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Jobs;

use App\Core\Events\EventEnvelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessVehicleCreatedJob implements ShouldQueue
{
    public function __construct(
        public EventEnvelope $event
    ) {}

    public function handle(): void
    {
        Log::info('ASYNC VEHICLE EVENT PROCESSING', [
            'event_type' => $this->event->eventType,
            'trace_id' => $this->event->traceId,
            'tenant_id' => $this->event->tenantId,
            'event_id' => $this->event->eventId,
        ]);

        // enterprise hooks:
        // - notifications
        // - external API sync
        // - analytics pipeline
        // - billing triggers
    }
}