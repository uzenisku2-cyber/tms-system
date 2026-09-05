<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleCostAllocation;
use App\Modules\Fleet\Models\VehicleCostAllocationEvent;
use App\Modules\Fleet\Models\VehicleCostAllocationLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class VehicleCostAllocationApplicationService
{
    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function create(array $data, int $organizationId, User $actor): array
    {
        $this->authorizeContext($actor, $organizationId);
        $vehicle = Vehicle::query()->whereKey((int) $data['vehicle_id'])->firstOrFail();
        $covered = $vehicle->ownerships()->where('organization_context_id', $organizationId)->exists()
            || $vehicle->responsibilities()->where('organization_context_id', $organizationId)->exists();
        if (! $covered) {
            abort(404);
        }
        $lines = array_values($data['lines']);
        $this->validateLines($lines, $organizationId, $actor, (string) $data['currency']);

        return DB::transaction(function () use ($data, $lines, $organizationId, $actor, $vehicle): array {
            $totals = $this->totals($lines);
            $allocationUid = (string) Str::uuid();
            $allocation = VehicleCostAllocation::query()->create([
                'public_id' => (string) Str::uuid(), 'allocation_uid' => $allocationUid,
                'vehicle_id' => $vehicle->getKey(), 'organization_context_id' => $organizationId,
                'source_type' => $data['source_type'], 'source_reference_uid' => $data['source_reference_uid'] ?? null,
                'source_document_reference' => $data['source_document_reference'] ?? null,
                'occurred_on' => $data['occurred_on'], 'description' => $data['description'],
                'net_amount' => $totals['net'], 'vat_amount' => $totals['vat'], 'gross_amount' => $totals['gross'],
                'currency' => $data['currency'], 'status' => 'draft', 'recorded_by_user_id' => $actor->getKey(),
                'revision' => 1, 'notes' => $data['notes'] ?? null,
            ]);
            $this->snapshotLines($allocation, $lines, $actor, 1);
            VehicleCostAllocationEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'vehicle_cost_allocation_id' => $allocation->getKey(),
                'event_type' => 'created', 'from_status' => null, 'to_status' => 'draft',
                'evidence' => ['financial_automation' => false], 'actor_user_id' => $actor->getKey(),
                'revision' => 1, 'occurred_at' => now(),
            ]);

            return $this->present($allocationUid, $organizationId);
        });
    }

    public function show(string $allocationUid, int $organizationId, User $actor): array
    {
        $this->authorizeContext($actor, $organizationId);

        return $this->present($allocationUid, $organizationId);
    }

    public function approve(string $allocationUid, int $expectedRevision, int $organizationId, User $actor): array
    {
        $this->authorizeContext($actor, $organizationId);

        return DB::transaction(function () use ($allocationUid, $expectedRevision, $organizationId, $actor): array {
            $current = VehicleCostAllocation::query()->where('allocation_uid', $allocationUid)
                ->where('organization_context_id', $organizationId)->orderByDesc('revision')->lockForUpdate()->firstOrFail();
            if ((int) $current->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The allocation revision has changed.']]);
            }
            if ($current->status === 'approved') {
                return $this->present($allocationUid, $organizationId);
            }
            if ($current->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only a draft allocation can be approved.']]);
            }
            $revision = $expectedRevision + 1;
            $approved = VehicleCostAllocation::query()->create([
                'public_id' => (string) Str::uuid(), 'allocation_uid' => $allocationUid,
                'vehicle_id' => $current->vehicle_id, 'organization_context_id' => $organizationId,
                'source_type' => $current->source_type, 'source_reference_uid' => $current->source_reference_uid,
                'source_document_reference' => $current->source_document_reference, 'occurred_on' => $current->occurred_on,
                'description' => $current->description, 'net_amount' => $current->net_amount,
                'vat_amount' => $current->vat_amount, 'gross_amount' => $current->gross_amount,
                'currency' => $current->currency, 'status' => 'approved', 'recorded_by_user_id' => $current->recorded_by_user_id,
                'approved_by_user_id' => $actor->getKey(), 'approved_at' => now(), 'revision' => $revision, 'notes' => $current->notes,
            ]);
            $sourceLines = $current->lines()->orderBy('sequence_number')->get()->map(fn ($line): array => $line->only(['line_uid', 'cost_component', 'responsible_party_type', 'responsible_organization_id', 'responsible_user_id', 'external_party_name', 'net_amount', 'vat_amount', 'gross_amount', 'currency', 'settlement_mode', 'vat_treatment', 'vat_rate_basis_points', 'notes']))->all();
            $this->snapshotLines($approved, $sourceLines, $actor, $revision);
            VehicleCostAllocationEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'vehicle_cost_allocation_id' => $approved->getKey(),
                'event_type' => 'approved', 'from_status' => 'draft', 'to_status' => 'approved',
                'evidence' => ['financial_automation' => false], 'actor_user_id' => $actor->getKey(),
                'revision' => $revision, 'occurred_at' => now(),
            ]);

            return $this->present($allocationUid, $organizationId);
        });
    }

    private function authorizeContext(User $actor, int $organizationId): void
    {
        if (! $actor->can('compensation.manage')) {
            abort(403, 'Compensation management permission is required.');
        }
        $this->authorization->findManageableOrganization($actor, $organizationId, $organizationId);
    }

    private function validateLines(array $lines, int $organizationId, User $actor, string $currency): void
    {
        foreach ($lines as $index => $line) {
            $type = (string) $line['responsible_party_type'];
            $organization = $line['responsible_organization_id'] ?? null;
            $user = $line['responsible_user_id'] ?? null;
            $external = $line['external_party_name'] ?? null;
            $valid = ($type === 'organization' && $organization && ! $user && ! $external)
                || ($type === 'driver' && $user && ! $organization && ! $external)
                || (in_array($type, ['insurer', 'external_party'], true) && $external && ! $organization && ! $user)
                || (in_array($type, ['state', 'internal'], true) && ! $organization && ! $user && ! $external);
            if (! $valid) {
                throw ValidationException::withMessages(["lines.$index.responsible_party_type" => ['Responsible party fields do not match the selected type.']]);
            }
            if ($type === 'organization') {
                $this->authorization->findManageableOrganization($actor, $organizationId, (int) $organization);
            }
            if ($type === 'driver') {
                $driver = Driver::query()->where('user_id', (int) $user)->firstOrFail();
                $this->authorization->findVisibleDriver($actor, $organizationId, (int) $driver->getKey());
            }
            if (isset($line['currency']) && $line['currency'] !== $currency) {
                throw ValidationException::withMessages(["lines.$index.currency" => ['Line currency must match the allocation currency.']]);
            }
            if ($this->minor((string) $line['gross_amount']) !== $this->minor((string) $line['net_amount']) + $this->minor((string) $line['vat_amount'])) {
                throw ValidationException::withMessages(["lines.$index.gross_amount" => ['Gross amount must equal net amount plus VAT.']]);
            }
        }
    }

    private function totals(array $lines): array
    {
        $net = $vat = $gross = 0;
        foreach ($lines as $line) {
            $net += $this->minor((string) $line['net_amount']);
            $vat += $this->minor((string) $line['vat_amount']);
            $gross += $this->minor((string) $line['gross_amount']);
        }

        return ['net' => $this->money($net), 'vat' => $this->money($vat), 'gross' => $this->money($gross)];
    }

    private function snapshotLines(VehicleCostAllocation $allocation, array $lines, User $actor, int $revision): void
    {
        foreach ($lines as $index => $line) {
            VehicleCostAllocationLine::query()->create(array_merge($line, ['public_id' => (string) Str::uuid(), 'line_uid' => $line['line_uid'] ?? (string) Str::uuid(), 'vehicle_cost_allocation_id' => $allocation->getKey(), 'sequence_number' => $index + 1, 'currency' => $allocation->currency, 'recorded_by_user_id' => $actor->getKey(), 'revision' => $revision]));
        }
    }

    private function present(string $allocationUid, int $organizationId): array
    {
        $revisions = VehicleCostAllocation::query()->where('allocation_uid', $allocationUid)->where('organization_context_id', $organizationId)->with(['lines' => fn ($query) => $query->orderBy('sequence_number'), 'events'])->orderBy('revision')->get();
        if ($revisions->isEmpty()) {
            abort(404);
        }
        $current = $revisions->last();

        return ['allocation_uid' => $allocationUid, 'status' => $current->status, 'revision' => (int) $current->revision, 'vehicle_id' => (int) $current->vehicle_id, 'net_amount' => $current->net_amount, 'vat_amount' => $current->vat_amount, 'gross_amount' => $current->gross_amount, 'currency' => $current->currency, 'lines' => $current->lines->toArray(), 'events' => $revisions->flatMap(fn (VehicleCostAllocation $item) => $item->events)->sortBy('revision')->values()->toArray(), 'financial_automation_performed' => false];
    }

    private function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function money(int $minor): string
    {
        return sprintf('%d.%02d', intdiv($minor, 100), $minor % 100);
    }
}
