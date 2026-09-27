<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleRegistryEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleLifecycleTransitionService
{
    public function __construct(private readonly VehicleRegistryAdministrationReadService $readService) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transition(string $publicId, array $data, int $organizationId, User $actor): array
    {
        abort_unless($actor->can('vehicle.manage'), 403);
        // Reuse the registry's organization membership and visibility rules.
        $this->readService->show($publicId, $organizationId, $actor);

        DB::transaction(function () use ($publicId, $data, $organizationId, $actor): void {
            $vehicle = Vehicle::query()->where('public_id', $publicId)->lockForUpdate()->first();
            if (! $vehicle instanceof Vehicle || ! $vehicle->responsibilities()
                ->where('organization_context_id', $organizationId)->where('status', 'active')->exists()) {
                throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
            }
            if ((int) $vehicle->current_revision !== (int) $data['expected_revision']) {
                throw new ConflictHttpException('Vehicle revision is stale.');
            }
            $from = (string) $vehicle->lifecycle_status;
            $to = (string) $data['target_status'];
            if (! VehicleLifecycleTransitionPolicy::permits($from, $to)) {
                throw ValidationException::withMessages(['target_status' => ['Vehicle lifecycle transition is not allowed.']]);
            }
            if ($from === 'active' && $vehicle->hasActiveTrip()) {
                throw new ConflictHttpException('Vehicle has an assigned or started trip.');
            }

            $nextRevision = (int) $vehicle->current_revision + 1;
            $vehicle->update([
                'lifecycle_status' => $to,
                'active' => $to === 'active',
                'archived_at' => $to === 'archived' ? now() : null,
                'current_revision' => $nextRevision,
            ]);
            VehicleRegistryEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'vehicle_id' => $vehicle->id,
                'organization_context_id' => $organizationId,
                'actor_user_id' => $actor->id,
                'event_type' => 'vehicle_lifecycle_transitioned',
                'vehicle_revision' => $nextRevision,
                'reason' => (string) $data['reason'],
                'payload' => ['from_status' => $from, 'to_status' => $to],
                'occurred_at' => now(),
            ]);
        });

        return $this->readService->show($publicId, $organizationId, $actor);
    }
}
