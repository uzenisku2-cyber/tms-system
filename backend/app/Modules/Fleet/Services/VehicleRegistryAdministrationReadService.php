<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class VehicleRegistryAdministrationReadService
{
    public function index(array $filters, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);
        $query = $this->visibleQuery($organizationId)->with([
            'recordFieldStatuses' => fn ($query) => $query->where('organization_context_id', $organizationId),
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $builder) use ($search): void {
                $builder->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }
        if (isset($filters['lifecycle_status'])) {
            $query->where('lifecycle_status', $filters['lifecycle_status']);
        }
        if (array_key_exists('active', $filters)) {
            $query->where('active', (bool) $filters['active']);
        }
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 25)));
        $total = (clone $query)->count();
        /** @var Collection<int, Vehicle> $vehicles */
        $vehicles = $query->orderBy('registration_number')->forPage($page, $perPage)->get();
        $items = $vehicles->map(fn (Vehicle $vehicle): array => $this->summary($vehicle))->values()->all();

        return ['items' => $items, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))], 'capabilities' => ['can_view_vehicles' => true, 'can_manage_vehicles' => $actor->can('vehicle.manage')]];
    }

    public function show(string $publicId, int $organizationId, User $actor): array
    {
        $this->authorize($actor, $organizationId);
        $vehicle = $this->visibleQuery($organizationId)->where('public_id', $publicId)->first();
        if (! $vehicle instanceof Vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$publicId]);
        }
        $vehicle->load(['ownerships' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('valid_from'), 'responsibilities' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('valid_from'), 'documents' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'complianceRecords' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'insurancePolicies' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'serviceRecords' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'incidents' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'financingAgreements' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderByDesc('id'), 'registryEvents' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderBy('vehicle_revision'), 'recordFieldStatuses' => fn ($q) => $q->where('organization_context_id', $organizationId)->orderBy('field_key')]);

        return ['vehicle' => $this->summary($vehicle), 'ownerships' => $vehicle->ownerships->toArray(), 'responsibilities' => $vehicle->responsibilities->toArray(), 'documents' => $vehicle->documents->toArray(), 'compliance_records' => $vehicle->complianceRecords->toArray(), 'insurance_policies' => $vehicle->insurancePolicies->toArray(), 'service_records' => $vehicle->serviceRecords->toArray(), 'incidents' => $vehicle->incidents->toArray(), 'financing_agreements' => $vehicle->financingAgreements->toArray(), 'events' => $vehicle->registryEvents->toArray(), 'field_statuses' => $vehicle->recordFieldStatuses->toArray(), 'completeness' => $this->completeness($vehicle), 'capabilities' => ['can_manage_vehicles' => $actor->can('vehicle.manage')]];
    }

    private function visibleQuery(int $organizationId): Builder
    {
        return Vehicle::query()->where(static function (Builder $query) use ($organizationId): void {
            $query->whereHas('ownerships', fn (Builder $q) => $q->where('organization_context_id', $organizationId))
                ->orWhereHas('responsibilities', fn (Builder $q) => $q->where('organization_context_id', $organizationId));
        });
    }

    private function authorize(User $actor, int $organizationId): void
    {
        abort_unless($actor->can('vehicle.view'), 403);
        $now = now();
        $membershipExists = $actor->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->where(static function (Builder $query) use ($now): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(static function (Builder $query) use ($now): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->whereHas('organization', static function (Builder $query): void {
                $query->where('status', Organization::STATUS_ACTIVE);
            })
            ->exists();
        abort_unless($membershipExists, 403, 'Organization access denied.');
    }

    private function completeness(Vehicle $vehicle): array
    {
        $statuses = $vehicle->recordFieldStatuses;
        $complete = $statuses->filter(static fn ($status): bool => in_array($status->status, ['verified', 'not_applicable'], true))->count();

        return ['complete' => $complete, 'total' => $statuses->count(), 'percentage' => $statuses->count() === 0 ? 0 : (int) floor(($complete * 100) / $statuses->count())];
    }

    private function summary(Vehicle $vehicle): array
    {
        $mileageIsUnknown = $vehicle->recordFieldStatuses
            ->where('field_key', 'mileage')
            ->whereIn('status', ['missing', 'pending_document'])
            ->isNotEmpty();

        return ['public_id' => $vehicle->public_id, 'registration_number' => $vehicle->registration_number, 'vin' => $vehicle->vin, 'manufacturer' => $vehicle->manufacturer, 'model' => $vehicle->model, 'year' => $vehicle->year, 'vehicle_type' => $vehicle->vehicle_type, 'fuel_type' => $vehicle->fuel_type, 'mileage' => $mileageIsUnknown ? null : $vehicle->mileage, 'odometer_unit' => $vehicle->odometer_unit, 'lifecycle_status' => $vehicle->lifecycle_status, 'revision' => (int) $vehicle->current_revision, 'active' => (bool) $vehicle->active, 'archived_at' => $vehicle->archived_at?->toIso8601String()];
    }
}
