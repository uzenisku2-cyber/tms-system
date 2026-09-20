<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriodEvent;
use Illuminate\Support\Facades\DB;

final class FinancialSettlementAccountingPeriodService
{
    /** @param array<string, mixed> $command */
    public function create(int $organizationId, User $actor, array $command): FinancialSettlementAccountingPeriod
    {
        $fingerprint = hash('sha256', json_encode(['create', $organizationId, $command['period_start'], $command['period_end'], strtoupper((string) $command['currency']), $command['reason']], JSON_THROW_ON_ERROR));
        if (($replay = $this->replay((string) $command['idempotency_key'], $fingerprint)) !== null) {
            return $replay;
        }

        return DB::transaction(function () use ($organizationId, $actor, $command, $fingerprint): FinancialSettlementAccountingPeriod {
            abort_if(FinancialSettlementAccountingPeriod::query()->where('owner_organization_id', $organizationId)->where('currency', strtoupper((string) $command['currency']))->whereDate('period_start', '<=', $command['period_end'])->whereDate('period_end', '>=', $command['period_start'])->lockForUpdate()->exists(), 409, 'The accounting period overlaps an existing period.');
            $period = FinancialSettlementAccountingPeriod::query()->create(['owner_organization_id' => $organizationId, 'period_start' => $command['period_start'], 'period_end' => $command['period_end'], 'currency' => strtoupper((string) $command['currency']), 'status' => FinancialSettlementAccountingPeriod::STATUS_OPEN, 'revision' => 1, 'last_reason' => $command['reason']]);
            $this->event($period, $actor, (string) $command['idempotency_key'], $fingerprint, 'accounting_period_opened', 1, $command);

            return $period;
        });
    }

    /** @param array<string, mixed> $command */
    public function transition(int $organizationId, string $publicId, User $actor, array $command, string $action): FinancialSettlementAccountingPeriod
    {
        abort_unless(in_array($action, ['close', 'reopen'], true), 422);
        $fingerprint = hash('sha256', json_encode([$action, $organizationId, $publicId, (int) $command['expected_revision'], $command['reason']], JSON_THROW_ON_ERROR));
        if (($replay = $this->replay((string) $command['idempotency_key'], $fingerprint)) !== null) {
            return $replay;
        }

        return DB::transaction(function () use ($organizationId, $publicId, $actor, $command, $action, $fingerprint): FinancialSettlementAccountingPeriod {
            $period = FinancialSettlementAccountingPeriod::query()->where('owner_organization_id', $organizationId)->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            abort_if((int) $period->getAttribute('revision') !== (int) $command['expected_revision'], 409, 'The accounting period revision changed.');
            $expectedStatus = $action === 'close' ? [FinancialSettlementAccountingPeriod::STATUS_OPEN, FinancialSettlementAccountingPeriod::STATUS_REOPENED] : [FinancialSettlementAccountingPeriod::STATUS_CLOSED];
            abort_unless(in_array((string) $period->getAttribute('status'), $expectedStatus, true), 409, 'The accounting period transition is not allowed.');
            $revision = (int) $period->getAttribute('revision') + 1;
            $attributes = ['status' => $action === 'close' ? FinancialSettlementAccountingPeriod::STATUS_CLOSED : FinancialSettlementAccountingPeriod::STATUS_REOPENED, 'revision' => $revision, 'last_reason' => $command['reason']];
            if ($action === 'close') {
                $attributes += ['closed_by_user_id' => $actor->getKey(), 'closed_at' => now()];
            } else {
                $attributes += ['reopened_by_user_id' => $actor->getKey(), 'reopened_at' => now()];
            }
            $period->forceFill($attributes)->saveOrFail();
            $this->event($period, $actor, (string) $command['idempotency_key'], $fingerprint, $action === 'close' ? 'accounting_period_closed' : 'accounting_period_reopened', $revision, $command);

            return $period;
        });
    }

    private function replay(string $idempotencyKey, string $fingerprint): ?FinancialSettlementAccountingPeriod
    {
        $event = FinancialSettlementAccountingPeriodEvent::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($event === null) {
            return null;
        }
        abort_if((string) $event->getAttribute('command_fingerprint') !== $fingerprint, 409, 'The idempotency key was already used for another accounting-period command.');

        return FinancialSettlementAccountingPeriod::query()
            ->whereKey($event->getAttribute('accounting_period_id'))
            ->firstOrFail();
    }

    /** @param array<string, mixed> $payload */
    private function event(FinancialSettlementAccountingPeriod $period, User $actor, string $key, string $fingerprint, string $type, int $revision, array $payload): void
    {
        FinancialSettlementAccountingPeriodEvent::query()->create(['accounting_period_id' => $period->getKey(), 'event_type' => $type, 'idempotency_key' => $key, 'command_fingerprint' => $fingerprint, 'payload' => $payload, 'actor_user_id' => $actor->getKey(), 'occurred_at' => now(), 'revision' => $revision]);
    }
}
