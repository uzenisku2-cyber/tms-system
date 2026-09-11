<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\BillingDocumentLine;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Models\FinancialSettlementStatementEvent;
use App\Modules\Pricing\Models\FinancialSettlementStatementLine;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FinancialSettlementStatementService
{
    public function store(array $data, int $organizationId, User $actor): array
    {
        $command = $this->normalize($data);
        $fingerprint = hash('sha256', json_encode(Arr::sortRecursive($command), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return DB::transaction(function () use ($command, $fingerprint, $organizationId, $actor): array {
            $replay = FinancialSettlementStatement::query()->where('owner_organization_id', $organizationId)
                ->where('idempotency_key', $command['idempotency_key'])->first();
            if ($replay instanceof FinancialSettlementStatement) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($replay), 'replayed' => true];
            }
            if ($command['financial_calculation_public_ids'] === [] && $command['financial_mutual_charge_public_ids'] === []) {
                throw ValidationException::withMessages(['sources' => ['At least one financial source is required.']]);
            }
            $this->assertRecipientExists($command);
            $calculations = FinancialCalculation::query()->whereIn('public_id', $command['financial_calculation_public_ids'])->lockForUpdate()->get();
            $charges = FinancialMutualCharge::query()->whereIn('public_id', $command['financial_mutual_charge_public_ids'])->lockForUpdate()->get();
            if ($calculations->count() !== count($command['financial_calculation_public_ids'])) {
                throw ValidationException::withMessages(['financial_calculation_public_ids' => ['One or more calculations are unavailable.']]);
            }
            if ($charges->count() !== count($command['financial_mutual_charge_public_ids'])) {
                throw ValidationException::withMessages(['financial_mutual_charge_public_ids' => ['One or more mutual charges are unavailable.']]);
            }

            $lines = [];
            foreach ($calculations as $calculation) {
                $this->assertCalculation($calculation, $command, $organizationId);
                $lines[] = [
                    'source_type' => FinancialSettlementStatementLine::SOURCE_FINANCIAL_CALCULATION,
                    'source_public_id' => (string) $calculation->public_id,
                    'source_revision' => (int) $calculation->calculation_version,
                    'financial_calculation_id' => $calculation->id,
                    'financial_mutual_charge_id' => null,
                    'effect' => FinancialSettlementStatementLine::EFFECT_EARNING,
                    'description' => 'Approved performance calculation.',
                    'amount_minor' => $this->minor((string) $calculation->total_amount),
                    'source_snapshot' => ['status' => $calculation->status, 'calculation_version' => (int) $calculation->calculation_version, 'input' => $calculation->input_snapshot],
                ];
            }
            foreach ($charges as $charge) {
                $this->assertCharge($charge, $command, $organizationId);
                $effect = $charge->direction === FinancialMutualCharge::DIRECTION_PAYABLE
                    ? FinancialSettlementStatementLine::EFFECT_EARNING
                    : FinancialSettlementStatementLine::EFFECT_DEDUCTION;
                $lines[] = [
                    'source_type' => FinancialSettlementStatementLine::SOURCE_MUTUAL_CHARGE,
                    'source_public_id' => (string) $charge->public_id,
                    'source_revision' => (int) $charge->revision,
                    'financial_calculation_id' => null,
                    'financial_mutual_charge_id' => $charge->id,
                    'effect' => $effect,
                    'description' => (string) $charge->description,
                    'amount_minor' => (int) $charge->amount_minor,
                    'source_snapshot' => ['status' => $charge->status, 'direction' => $charge->direction, 'category' => $charge->category, 'source' => $charge->source_snapshot],
                ];
            }
            $earning = array_sum(array_column(array_filter($lines, static fn (array $line): bool => $line['effect'] === FinancialSettlementStatementLine::EFFECT_EARNING), 'amount_minor'));
            $deduction = array_sum(array_column(array_filter($lines, static fn (array $line): bool => $line['effect'] === FinancialSettlementStatementLine::EFFECT_DEDUCTION), 'amount_minor'));
            $statement = FinancialSettlementStatement::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId,
                'recipient_type' => $command['recipient_type'],
                'recipient_organization_id' => $command['recipient_organization_id'],
                'recipient_driver_id' => $command['recipient_driver_id'],
                'period_from' => $command['period_from'], 'period_until' => $command['period_until'],
                'currency' => $command['currency'], 'status' => FinancialSettlementStatement::STATUS_DRAFT,
                'earning_amount_minor' => $earning, 'deduction_amount_minor' => $deduction,
                'net_balance_minor' => $earning - $deduction,
                'source_snapshot' => ['calculation_count' => $calculations->count(), 'mutual_charge_count' => $charges->count()],
                'idempotency_key' => $command['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'revision' => 1, 'created_by_user_id' => $actor->id,
            ]);
            foreach ($lines as $position => $line) {
                FinancialSettlementStatementLine::query()->create(array_merge($line, [
                    'public_id' => (string) Str::uuid(), 'financial_settlement_statement_id' => $statement->id,
                    'position' => $position + 1, 'currency' => $command['currency'], 'created_at' => now(),
                ]));
            }
            FinancialSettlementStatementEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'financial_settlement_statement_id' => $statement->id,
                'revision' => 1, 'event_type' => 'draft_created', 'idempotency_key' => $command['idempotency_key'],
                'command_fingerprint' => $fingerprint, 'from_status' => null, 'to_status' => FinancialSettlementStatement::STATUS_DRAFT,
                'reason' => $command['reason'], 'evidence' => ['line_count' => count($lines), 'earning_amount_minor' => $earning, 'deduction_amount_minor' => $deduction],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($statement), 'replayed' => false];
        });
    }

    /** @param array<string,mixed> $data @return array{data:array<string,mixed>,replayed:bool} */
    public function transition(string $publicId, array $data, int $organizationId, User $actor): array
    {
        $action = (string) $data['action'];
        $key = (string) $data['idempotency_key'];
        $expectedRevision = (int) $data['expected_revision'];
        $reason = trim((string) $data['reason']);
        $fingerprint = hash('sha256', json_encode([
            'statement' => $publicId, 'action' => $action, 'expected_revision' => $expectedRevision,
            'reason' => $reason, 'organization_id' => $organizationId,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publicId, $organizationId, $actor, $action, $key, $expectedRevision, $reason, $fingerprint): array {
            $statement = FinancialSettlementStatement::query()
                ->where('public_id', $publicId)->where('owner_organization_id', $organizationId)
                ->lockForUpdate()->firstOrFail();
            $replay = FinancialSettlementStatementEvent::query()
                ->where('financial_settlement_statement_id', $statement->id)
                ->where('idempotency_key', $key)->first();
            if ($replay instanceof FinancialSettlementStatementEvent) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($statement->fresh()), 'replayed' => true];
            }
            if ((int) $statement->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The statement revision is stale.']]);
            }

            $from = (string) $statement->status;
            $allowed = [
                'submit' => [FinancialSettlementStatement::STATUS_DRAFT, FinancialSettlementStatement::STATUS_UNDER_REVIEW],
                'approve' => [FinancialSettlementStatement::STATUS_UNDER_REVIEW, FinancialSettlementStatement::STATUS_APPROVED],
                'close' => [FinancialSettlementStatement::STATUS_APPROVED, FinancialSettlementStatement::STATUS_CLOSED],
            ];
            if ($action === 'cancel') {
                if ($statement->billing_document_id !== null || $statement->output_materialized_at !== null) {
                    throw ValidationException::withMessages(['action' => ['A materialized statement output must be cancelled through its document lifecycle.']]);
                }
                if (! in_array($from, [FinancialSettlementStatement::STATUS_UNDER_REVIEW, FinancialSettlementStatement::STATUS_APPROVED, FinancialSettlementStatement::STATUS_CLOSED], true)) {
                    throw ValidationException::withMessages(['action' => ['Only a reviewed, approved or closed statement may be cancelled.']]);
                }
                $to = FinancialSettlementStatement::STATUS_CANCELLED;
            } else {
                [$required, $to] = $allowed[$action];
                if ($from !== $required) {
                    throw ValidationException::withMessages(['action' => ["Action {$action} is not allowed from status {$from}."]]);
                }
            }

            $revision = $expectedRevision + 1;
            $updates = ['status' => $to, 'revision' => $revision];
            if ($action === 'approve') {
                $updates['approved_by_user_id'] = $actor->id;
                $updates['approved_at'] = now();
            } elseif ($action === 'close') {
                $updates['closed_at'] = now();
            } elseif ($action === 'cancel') {
                $updates['cancelled_at'] = now();
            }
            $statement->forceFill($updates)->save();
            FinancialSettlementStatementEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'financial_settlement_statement_id' => $statement->id,
                'revision' => $revision, 'event_type' => 'statement_'.$action,
                'idempotency_key' => $key, 'command_fingerprint' => $fingerprint,
                'from_status' => $from, 'to_status' => $to, 'reason' => $reason,
                'evidence' => ['line_count' => $statement->lines()->count(), 'net_balance_minor' => (int) $statement->net_balance_minor],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($statement->fresh()), 'replayed' => false];
        });
    }

    /** @param array<string,mixed> $data @return array{data:array<string,mixed>,replayed:bool} */
    public function materializeOutput(string $publicId, array $data, int $organizationId, User $actor): array
    {
        $key = (string) $data['idempotency_key'];
        $expectedRevision = (int) $data['expected_revision'];
        $reason = trim((string) $data['reason']);
        $fingerprint = hash('sha256', json_encode(['statement' => $publicId, 'organization_id' => $organizationId, 'data' => Arr::sortRecursive($data)], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publicId, $data, $organizationId, $actor, $key, $expectedRevision, $reason, $fingerprint): array {
            $statement = FinancialSettlementStatement::query()->where('public_id', $publicId)
                ->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $replay = FinancialSettlementStatementEvent::query()->where('financial_settlement_statement_id', $statement->id)
                ->where('idempotency_key', $key)->first();
            if ($replay instanceof FinancialSettlementStatementEvent) {
                if (! hash_equals((string) $replay->command_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['idempotency_key' => ['The idempotency key was already used with a different command.']]);
                }

                return ['data' => $this->present($statement->fresh()), 'replayed' => true];
            }
            if ($statement->status !== FinancialSettlementStatement::STATUS_CLOSED) {
                throw ValidationException::withMessages(['statement' => ['Only a closed statement may be materialized.']]);
            }
            if ((int) $statement->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The statement revision is stale.']]);
            }
            if ($statement->output_materialized_at !== null || $statement->billing_document_id !== null) {
                throw ValidationException::withMessages(['statement' => ['The statement output is already materialized.']]);
            }

            $balance = (int) $statement->net_balance_minor;
            $document = null;
            $direction = 'none';
            if ($balance === 0) {
                $kind = FinancialSettlementStatement::OUTPUT_ZERO_BALANCE;
            } elseif ($statement->recipient_type === FinancialSettlementStatement::RECIPIENT_DRIVER) {
                $kind = $balance > 0 ? FinancialSettlementStatement::OUTPUT_DRIVER_PAYOUT : FinancialSettlementStatement::OUTPUT_DRIVER_DEDUCTION;
                $direction = 'internal';
                $amount = abs($balance);
                $document = $this->createOutputDocument($statement, $actor, BillingDocument::TYPE_DRIVER_REMUNERATION, $amount, 0, BillingDocument::VAT_NOT_APPLICABLE, null, $kind);
            } else {
                $kind = $balance > 0 ? FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE : FinancialSettlementStatement::OUTPUT_CARRIER_RECEIVABLE;
                $direction = $balance > 0 ? BillingDocumentCommercialIdentity::DIRECTION_PAYABLE : BillingDocumentCommercialIdentity::DIRECTION_RECEIVABLE;
                $document = $this->createCarrierOutputDocument($statement, $data, $actor, $key, $fingerprint, $direction, $kind);
            }

            $revision = $expectedRevision + 1;
            $statement->forceFill([
                'billing_document_id' => $document?->id, 'output_kind' => $kind,
                'output_direction' => $direction, 'output_materialized_at' => now(), 'revision' => $revision,
            ])->save();
            FinancialSettlementStatementEvent::query()->create([
                'public_id' => (string) Str::uuid(), 'financial_settlement_statement_id' => $statement->id,
                'revision' => $revision, 'event_type' => 'output_materialized', 'idempotency_key' => $key,
                'command_fingerprint' => $fingerprint, 'from_status' => $statement->status, 'to_status' => $statement->status,
                'reason' => $reason, 'evidence' => ['output_kind' => $kind, 'output_direction' => $direction, 'billing_document_public_id' => $document?->public_id],
                'actor_user_id' => $actor->id, 'occurred_at' => now(),
            ]);

            return ['data' => $this->present($statement->fresh()), 'replayed' => false];
        });
    }

    /** @param array<string,mixed> $data */
    private function createCarrierOutputDocument(FinancialSettlementStatement $statement, array $data, User $actor, string $key, string $fingerprint, string $direction, string $kind): BillingDocument
    {
        foreach (['document_number', 'issued_on', 'due_on', 'counterparty_name', 'vat_treatment', 'net_amount', 'vat_amount'] as $field) {
            if (! isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw ValidationException::withMessages([$field => ['This field is required for a carrier settlement document.']]);
            }
        }
        $net = $this->minor((string) $data['net_amount']);
        $vat = $this->minor((string) $data['vat_amount']);
        if ($net + $vat !== abs((int) $statement->net_balance_minor)) {
            throw ValidationException::withMessages(['net_amount' => ['Net amount plus VAT must equal the absolute statement balance.']]);
        }
        $vatTreatment = (string) $data['vat_treatment'];
        $rate = isset($data['vat_rate_basis_points']) ? (int) $data['vat_rate_basis_points'] : null;
        if ($vatTreatment === BillingDocument::VAT_STANDARD && ($rate === null || (int) round($net * $rate / 10000) !== $vat)) {
            throw ValidationException::withMessages(['vat_amount' => ['VAT amount does not match the net amount and VAT rate.']]);
        }
        if ($vatTreatment === BillingDocument::VAT_NOT_APPLICABLE && ($rate !== null || $vat !== 0)) {
            throw ValidationException::withMessages(['vat_amount' => ['VAT must be zero and the VAT rate omitted when VAT is not applicable.']]);
        }
        $document = $this->createOutputDocument($statement, $actor, BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT, $net, $vat, $vatTreatment, $rate, $kind);
        $snapshot = ['name' => trim((string) $data['counterparty_name']), 'registration_number' => $data['counterparty_registration_number'] ?? null, 'vat_number' => $data['counterparty_vat_number'] ?? null, 'account_identifier' => $data['counterparty_account_identifier'] ?? null];
        $identity = BillingDocumentCommercialIdentity::query()->create([
            'public_id' => (string) Str::uuid(), 'billing_document_id' => $document->id, 'owner_organization_id' => $statement->owner_organization_id,
            'direction' => $direction, 'document_number' => trim((string) $data['document_number']), 'variable_symbol' => $data['variable_symbol'] ?? null,
            'issued_on' => $data['issued_on'], 'taxable_supply_on' => $data['taxable_supply_on'] ?? null, 'due_on' => $data['due_on'],
            'counterparty_name' => $snapshot['name'], 'counterparty_registration_number' => $snapshot['registration_number'],
            'counterparty_vat_number' => $snapshot['vat_number'], 'counterparty_account_identifier' => $snapshot['account_identifier'],
            'counterparty_snapshot' => $snapshot, 'revision' => 1, 'idempotency_key' => $key, 'command_fingerprint' => $fingerprint, 'created_by_user_id' => $actor->id,
        ]);
        BillingDocumentCommercialIdentityEvent::query()->create([
            'public_id' => (string) Str::uuid(), 'billing_document_commercial_identity_id' => $identity->id,
            'revision' => 1, 'event_type' => 'created', 'reason' => 'Settlement output materialized.',
            'evidence' => ['financial_settlement_statement_public_id' => $statement->public_id], 'occurred_at' => now(), 'actor_user_id' => $actor->id,
        ]);

        return $document;
    }

    private function createOutputDocument(FinancialSettlementStatement $statement, User $actor, string $type, int $net, int $vat, string $vatTreatment, ?int $rate, string $kind): BillingDocument
    {
        $gross = $net + $vat;
        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $statement->owner_organization_id,
            'counterparty_organization_id' => $statement->recipient_organization_id, 'driver_id' => $statement->recipient_driver_id,
            'document_type' => $type, 'period_from' => $statement->getRawOriginal('period_from'), 'period_until' => $statement->getRawOriginal('period_until'),
            'currency' => $statement->currency, 'vat_treatment' => $vatTreatment,
            'vat_status_snapshot' => $vatTreatment === BillingDocument::VAT_STANDARD ? 'payer' : 'non_payer',
            'net_amount' => $this->money($net), 'vat_rate' => $rate === null ? null : number_format($rate / 100, 2, '.', ''),
            'vat_amount' => $this->money($vat), 'gross_amount' => $this->money($gross), 'status' => 'draft',
            'source_snapshot' => ['financial_settlement_statement_public_id' => $statement->public_id, 'output_kind' => $kind, 'payment_marked' => false, 'bank_matching_performed' => false],
            'created_by_user_id' => $actor->id,
        ]);
        BillingDocumentLine::query()->create([
            'billing_document_id' => $document->id, 'financial_calculation_id' => null,
            'description' => 'Financial settlement statement '.$statement->public_id, 'quantity' => '1.000',
            'unit_rate' => $this->money($net), 'net_amount' => $this->money($net), 'vat_amount' => $this->money($vat),
            'gross_amount' => $this->money($gross), 'position' => 1, 'created_at' => now(),
        ]);

        return $document;
    }

    private function money(int $minor): string
    {
        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    private function normalize(array $data): array
    {
        return [
            'idempotency_key' => (string) $data['idempotency_key'], 'recipient_type' => (string) $data['recipient_type'],
            'recipient_organization_id' => $data['recipient_type'] === FinancialSettlementStatement::RECIPIENT_ORGANIZATION ? (int) $data['recipient_organization_id'] : null,
            'recipient_driver_id' => $data['recipient_type'] === FinancialSettlementStatement::RECIPIENT_DRIVER ? (int) $data['recipient_driver_id'] : null,
            'period_from' => (string) $data['period_from'], 'period_until' => (string) $data['period_until'],
            'currency' => strtoupper((string) $data['currency']),
            'financial_calculation_public_ids' => array_values($data['financial_calculation_public_ids']),
            'financial_mutual_charge_public_ids' => array_values($data['financial_mutual_charge_public_ids']),
            'reason' => trim((string) $data['reason']),
        ];
    }

    private function assertRecipientExists(array $command): void
    {
        if ($command['recipient_type'] === FinancialSettlementStatement::RECIPIENT_ORGANIZATION) {
            Organization::query()->findOrFail($command['recipient_organization_id']);
        } else {
            Driver::query()->findOrFail($command['recipient_driver_id']);
        }
    }

    private function assertCalculation(FinancialCalculation $calculation, array $command, int $organizationId): void
    {
        if ($calculation->status !== FinancialCalculation::STATUS_APPROVED || $calculation->currency !== $command['currency']) {
            throw ValidationException::withMessages(['financial_calculation_public_ids' => ['Calculations must be approved and use the statement currency.']]);
        }
        $snapshot = $calculation->input_snapshot;
        $matches = $command['recipient_type'] === FinancialSettlementStatement::RECIPIENT_ORGANIZATION
            ? (int) $calculation->organization_id === $command['recipient_organization_id']
            : is_array($snapshot) && (int) ($snapshot['performed_by_driver_id'] ?? 0) === $command['recipient_driver_id'];
        if (! $matches || ($command['recipient_type'] === FinancialSettlementStatement::RECIPIENT_DRIVER && (int) $calculation->organization_id !== $organizationId)) {
            throw ValidationException::withMessages(['financial_calculation_public_ids' => ['A calculation does not belong to the selected recipient.']]);
        }
        $report = $calculation->dailyReport;
        $serviceDate = $report instanceof DailyReport ? substr((string) $report->getRawOriginal('service_date'), 0, 10) : '';
        if ($serviceDate < $command['period_from'] || $serviceDate > $command['period_until']) {
            throw ValidationException::withMessages(['financial_calculation_public_ids' => ['A calculation is outside the statement period.']]);
        }
    }

    private function assertCharge(FinancialMutualCharge $charge, array $command, int $organizationId): void
    {
        $matches = $command['recipient_type'] === FinancialSettlementStatement::RECIPIENT_ORGANIZATION
            ? (int) $charge->counterparty_organization_id === $command['recipient_organization_id']
            : (int) $charge->counterparty_driver_id === $command['recipient_driver_id'];
        if ((int) $charge->owner_organization_id !== $organizationId || ! $matches || $charge->status !== FinancialMutualCharge::STATUS_CONFIRMED || ! $charge->offset_eligible || $charge->currency !== $command['currency']) {
            throw ValidationException::withMessages(['financial_mutual_charge_public_ids' => ['Mutual charges must be confirmed, eligible and belong to the statement parties and currency.']]);
        }
        $from = substr((string) $charge->getRawOriginal('service_period_from'), 0, 10);
        $until = substr((string) $charge->getRawOriginal('service_period_until'), 0, 10);
        if ($from < $command['period_from'] || $until > $command['period_until']) {
            throw ValidationException::withMessages(['financial_mutual_charge_public_ids' => ['A mutual charge is outside the statement period.']]);
        }
    }

    private function minor(string $amount): int
    {
        if (preg_match('/^(\d+)\.(\d{2})$/', $amount, $parts) !== 1) {
            throw new \LogicException('Financial calculation amount is not an exact two-decimal value.');
        }

        return ((int) $parts[1] * 100) + (int) $parts[2];
    }

    private function present(FinancialSettlementStatement $statement): array
    {
        $statement->loadMissing(['lines', 'events', 'billingDocument.commercialIdentity', 'billingDocument.lines']);

        return [
            'public_id' => $statement->public_id, 'recipient_type' => $statement->recipient_type,
            'recipient_organization_id' => $statement->recipient_organization_id === null ? null : (int) $statement->recipient_organization_id,
            'recipient_driver_id' => $statement->recipient_driver_id === null ? null : (int) $statement->recipient_driver_id,
            'period_from' => (string) $statement->getRawOriginal('period_from'), 'period_until' => (string) $statement->getRawOriginal('period_until'),
            'currency' => $statement->currency, 'status' => $statement->status,
            'earning_amount_minor' => (int) $statement->earning_amount_minor,
            'deduction_amount_minor' => (int) $statement->deduction_amount_minor,
            'net_balance_minor' => (int) $statement->net_balance_minor, 'revision' => (int) $statement->revision,
            'lines' => $statement->lines->toArray(), 'events' => $statement->events->toArray(),
            'output_kind' => $statement->output_kind, 'output_direction' => $statement->output_direction,
            'output_materialized_at' => (blank($statement->output_materialized_at) ? null : CarbonImmutable::parse((string) $statement->output_materialized_at)->toAtomString()),
            'billing_document' => $statement->billingDocument?->toArray(),
            'billing_document_created' => $statement->billing_document_id !== null,
            'payment_marked' => false, 'bank_matching_performed' => false,
        ];
    }
}
