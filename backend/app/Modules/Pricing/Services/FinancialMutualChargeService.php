<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use App\Modules\Pricing\Models\FinancialMutualChargeEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialMutualChargeService
{
    public function store(array $data, int $organizationId, User $actor): array
    {
        $normalized = $this->normalizeStore($data);
        $fingerprint = $this->fingerprint(['action' => 'create', 'data' => $normalized]);

        return DB::transaction(function () use ($normalized, $fingerprint, $organizationId, $actor): array {
            $replay = FinancialMutualCharge::query()
                ->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $normalized['idempotency_key'])
                ->first();
            if ($replay instanceof FinancialMutualCharge) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($replay), 'replayed' => true];
            }

            $this->assertCounterparty($normalized);
            $charge = FinancialMutualCharge::query()->create(array_merge($normalized, [
                'public_id' => (string) Str::uuid(),
                'owner_organization_id' => $organizationId,
                'command_fingerprint' => $fingerprint,
                'status' => FinancialMutualCharge::STATUS_DRAFT,
                'visibility_status' => FinancialMutualCharge::VISIBILITY_PRIVATE,
                'revision' => 1,
                'created_by_user_id' => $actor->id,
            ]));
            FinancialMutualChargeEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_mutual_charge_id' => $charge->id,
                'revision' => 1,
                'event_type' => FinancialMutualChargeEvent::TYPE_CREATED,
                'idempotency_key' => $normalized['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'from_status' => null,
                'to_status' => FinancialMutualCharge::STATUS_DRAFT,
                'reason' => 'Financial mutual charge draft created.',
                'evidence' => ['source_type' => $normalized['source_type'], 'source_public_id' => $normalized['source_public_id']],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($charge), 'replayed' => false];
        });
    }

    public function confirm(string $publicId, array $data, int $organizationId, User $actor): array
    {
        return $this->transition($publicId, $data, $organizationId, $actor, 'confirm');
    }

    public function dispute(string $publicId, array $data, int $organizationId, User $actor): array
    {
        return $this->transition($publicId, $data, $organizationId, $actor, 'dispute');
    }

    public function reverse(string $publicId, array $data, int $organizationId, User $actor): array
    {
        return $this->transition($publicId, $data, $organizationId, $actor, 'reverse');
    }

    private function transition(string $publicId, array $data, int $organizationId, User $actor, string $action): array
    {
        $normalized = [
            'idempotency_key' => (string) $data['idempotency_key'],
            'expected_revision' => (int) $data['expected_revision'],
            'reason' => trim((string) $data['reason']),
        ];
        $fingerprint = $this->fingerprint(['action' => $action, 'data' => $normalized]);

        return DB::transaction(function () use ($publicId, $normalized, $fingerprint, $organizationId, $actor, $action): array {
            $query = FinancialMutualCharge::query()->where('public_id', $publicId);
            if ($action === 'dispute') {
                $query->where(function ($scope) use ($organizationId): void {
                    $scope->where('owner_organization_id', $organizationId)
                        ->orWhere(function ($counterparty) use ($organizationId): void {
                            $counterparty->where('counterparty_organization_id', $organizationId)
                                ->where('visibility_status', FinancialMutualCharge::VISIBILITY_SHARED);
                        });
                });
            } else {
                $query->where('owner_organization_id', $organizationId);
            }
            $charge = $query->lockForUpdate()->firstOrFail();
            $event = FinancialMutualChargeEvent::query()
                ->where('financial_mutual_charge_id', $charge->id)
                ->where('idempotency_key', $normalized['idempotency_key'])
                ->first();
            if ($event instanceof FinancialMutualChargeEvent) {
                if (! hash_equals((string) $event->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($charge), 'replayed' => true];
            }
            if ((int) $charge->revision !== $normalized['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => ['The financial mutual charge revision is stale.']]);
            }

            [$requiredStatus, $newStatus, $eventType] = match ($action) {
                'confirm' => [FinancialMutualCharge::STATUS_DRAFT, FinancialMutualCharge::STATUS_CONFIRMED, FinancialMutualChargeEvent::TYPE_CONFIRMED],
                'dispute' => [FinancialMutualCharge::STATUS_CONFIRMED, FinancialMutualCharge::STATUS_DISPUTED, FinancialMutualChargeEvent::TYPE_DISPUTED],
                'reverse' => [null, FinancialMutualCharge::STATUS_REVERSED, FinancialMutualChargeEvent::TYPE_REVERSED],
                default => throw new \LogicException('Unsupported mutual charge transition.'),
            };
            if ($requiredStatus !== null && $charge->status !== $requiredStatus) {
                throw ValidationException::withMessages(['status' => ["The charge cannot be {$action}ed from its current status."]]);
            }
            if ($action === 'reverse' && $charge->status === FinancialMutualCharge::STATUS_REVERSED) {
                throw ValidationException::withMessages(['status' => ['The charge is already reversed.']]);
            }

            $fromStatus = (string) $charge->status;
            $nextRevision = (int) $charge->revision + 1;
            $updates = ['status' => $newStatus, 'revision' => $nextRevision];
            if ($action === 'confirm') {
                $updates += [
                    'visibility_status' => FinancialMutualCharge::VISIBILITY_SHARED,
                    'confirmed_by_user_id' => $actor->id,
                    'confirmed_at' => now(),
                    'shared_at' => now(),
                ];
            }
            if ($action === 'reverse') {
                $updates['reversed_at'] = now();
            }
            $charge->forceFill($updates)->save();

            FinancialMutualChargeEvent::query()->create([
                'public_id' => (string) Str::uuid(),
                'financial_mutual_charge_id' => $charge->id,
                'revision' => $nextRevision,
                'event_type' => $eventType,
                'idempotency_key' => $normalized['idempotency_key'],
                'command_fingerprint' => $fingerprint,
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'reason' => $normalized['reason'],
                'evidence' => ['acted_in_organization_id' => $organizationId],
                'actor_user_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            return ['data' => $this->present($charge), 'replayed' => false];
        });
    }

    private function normalizeStore(array $data): array
    {
        return [
            'idempotency_key' => (string) $data['idempotency_key'],
            'counterparty_type' => (string) $data['counterparty_type'],
            'counterparty_organization_id' => $data['counterparty_type'] === FinancialMutualCharge::PARTY_ORGANIZATION ? (int) $data['counterparty_organization_id'] : null,
            'counterparty_driver_id' => $data['counterparty_type'] === FinancialMutualCharge::PARTY_DRIVER ? (int) $data['counterparty_driver_id'] : null,
            'direction' => (string) $data['direction'],
            'category' => (string) $data['category'],
            'description' => trim((string) $data['description']),
            'service_period_from' => (string) $data['service_period_from'],
            'service_period_until' => (string) $data['service_period_until'],
            'amount_minor' => (int) $data['amount_minor'],
            'currency' => strtoupper((string) $data['currency']),
            'vat_treatment' => (string) $data['vat_treatment'],
            'offset_eligible' => (bool) $data['offset_eligible'],
            'source_type' => trim((string) $data['source_type']),
            'source_public_id' => (string) $data['source_public_id'],
            'source_snapshot' => Arr::sortRecursive($data['source_snapshot']),
        ];
    }

    private function assertCounterparty(array $data): void
    {
        if ($data['counterparty_type'] === FinancialMutualCharge::PARTY_ORGANIZATION) {
            Organization::query()->findOrFail($data['counterparty_organization_id']);

            return;
        }
        Driver::query()->findOrFail($data['counterparty_driver_id']);
    }

    private function fingerprint(array $command): string
    {
        return hash('sha256', json_encode(Arr::sortRecursive($command), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function present(FinancialMutualCharge $charge): array
    {
        $charge->loadMissing('events');

        return [
            'public_id' => $charge->public_id,
            'owner_organization_id' => (int) $charge->owner_organization_id,
            'counterparty_type' => $charge->counterparty_type,
            'counterparty_organization_id' => $charge->counterparty_organization_id === null ? null : (int) $charge->counterparty_organization_id,
            'counterparty_driver_id' => $charge->counterparty_driver_id === null ? null : (int) $charge->counterparty_driver_id,
            'direction' => $charge->direction,
            'category' => $charge->category,
            'description' => $charge->description,
            'service_period_from' => (string) $charge->getRawOriginal('service_period_from'),
            'service_period_until' => (string) $charge->getRawOriginal('service_period_until'),
            'amount_minor' => (int) $charge->amount_minor,
            'currency' => $charge->currency,
            'vat_treatment' => $charge->vat_treatment,
            'offset_eligible' => (bool) $charge->offset_eligible,
            'status' => $charge->status,
            'visibility_status' => $charge->visibility_status,
            'source_type' => $charge->source_type,
            'source_public_id' => $charge->source_public_id,
            'source_snapshot' => $charge->source_snapshot,
            'revision' => (int) $charge->revision,
            'events' => $charge->events->toArray(),
            'settlement_statement_created' => false,
            'billing_document_created' => false,
            'bank_matching_performed' => false,
            'payment_marked' => false,
        ];
    }
}
