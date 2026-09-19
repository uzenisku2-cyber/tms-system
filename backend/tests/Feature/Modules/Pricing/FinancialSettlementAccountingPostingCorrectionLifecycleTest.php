<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrection;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingCorrectionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingCorrectionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_correction_http_lifecycle_creates_one_balanced_replacement_and_preserves_sources(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $organization = Organization::query()->create(['name' => 'S090 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S090 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization);
        $foreignActor = $this->actorFor($foreignOrganization);

        $execution = new FinancialSettlementAccountingPostingExecution;
        $execution->fill($this->attributesFor($execution, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_handoff_id' => 900001,
            'revision' => 3,
            'status' => FinancialSettlementAccountingPostingExecution::STATUS_POSTED,
            'amount_minor' => 50000,
            'currency' => 'CZK',
            'direction' => 'payable',
            'executed_by_user_id' => $actor->id,
        ]));
        $execution->saveOrFail();
        $debit = $this->createEntry($execution, $organization, 1, 'debit', '311000', 50000);
        $credit = $this->createEntry($execution, $organization, 2, 'credit', '395000', 50000);

        $reversal = new FinancialSettlementAccountingPostingReversal;
        $reversal->fill($this->attributesFor($reversal, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->id,
            'revision' => 1,
            'currency' => 'CZK',
            'total_debit_minor' => 50000,
            'total_credit_minor' => 50000,
            'reversed_by_user_id' => $actor->id,
        ]));
        $reversal->saveOrFail();

        $executionSnapshot = $execution->fresh()?->getAttributes();
        $debitSnapshot = $debit->fresh()?->getAttributes();
        $creditSnapshot = $credit->fresh()?->getAttributes();
        $reversalSnapshot = $reversal->fresh()?->getAttributes();
        self::assertIsArray($executionSnapshot);
        self::assertIsArray($debitSnapshot);
        self::assertIsArray($creditSnapshot);
        self::assertIsArray($reversalSnapshot);

        $idempotencyKey = (string) Str::uuid();
        $uri = '/api/v1/financial-settlement-accounting-posting-executions/'.$execution->public_id.'/correction';
        $command = [
            'idempotency_key' => $idempotencyKey,
            'expected_revision' => 3,
            'reason' => 'S090 controlled accounting correction.',
            'posting_date' => now()->toDateString(),
            'accounting_reference' => 'S090-CORRECTION-001',
            'description' => 'S090 balanced replacement posting.',
            'entries' => [
                ['side' => 'debit', 'account_code' => '311100', 'amount_minor' => 50000, 'description' => 'Corrected debit.'],
                ['side' => 'credit', 'account_code' => '395100', 'amount_minor' => 50000, 'description' => 'Corrected credit.'],
            ],
        ];

        $response = $this->postAs($actor, $organization, $uri, $command);
        $response->assertCreated();
        $publicId = (string) $response->json('data.public_id');
        self::assertNotSame('', $publicId);

        $correction = FinancialSettlementAccountingPostingCorrection::query()->where('public_id', $publicId)->firstOrFail();
        self::assertSame((int) $organization->id, (int) $correction->owner_organization_id);
        self::assertSame((int) $execution->id, (int) $correction->original_execution_id);
        self::assertSame((int) $reversal->id, (int) $correction->reversal_id);
        self::assertSame(1, (int) $correction->revision);

        $replacement = FinancialSettlementAccountingPostingExecution::query()->findOrFail($correction->replacement_execution_id);
        self::assertNotSame((int) $execution->id, (int) $replacement->id);
        self::assertSame((int) $organization->id, (int) $replacement->owner_organization_id);
        self::assertSame(50000, (int) $replacement->amount_minor);
        self::assertSame('CZK', (string) $replacement->currency);
        self::assertSame(FinancialSettlementAccountingPostingExecution::STATUS_POSTED, (string) $replacement->status);

        $replacementEntries = FinancialSettlementAccountingPostingEntry::query()
            ->where('financial_settlement_accounting_posting_execution_id', $replacement->id)
            ->orderBy('sequence_number')
            ->get();
        self::assertCount(2, $replacementEntries);
        self::assertSame(50000, (int) $replacementEntries->where('side', 'debit')->sum('amount_minor'));
        self::assertSame(50000, (int) $replacementEntries->where('side', 'credit')->sum('amount_minor'));
        self::assertSame('311100', (string) $replacementEntries[0]->account_code);
        self::assertSame('395100', (string) $replacementEntries[1]->account_code);
        self::assertSame(1, FinancialSettlementAccountingPostingCorrectionEvent::query()->where('correction_id', $correction->id)->count());

        $this->postAs($actor, $organization, $uri, $command)
            ->assertCreated()
            ->assertJsonPath('data.public_id', $publicId);
        self::assertSame(1, FinancialSettlementAccountingPostingCorrection::query()->count());
        self::assertSame(4, FinancialSettlementAccountingPostingEntry::query()->count());
        self::assertSame(1, FinancialSettlementAccountingPostingCorrectionEvent::query()->count());

        $conflict = $command;
        $conflict['reason'] = 'Different correction command.';
        $this->postAs($actor, $organization, $uri, $conflict)->assertConflict();

        $stale = $command;
        $stale['idempotency_key'] = (string) Str::uuid();
        $stale['expected_revision'] = 4;
        $this->postAs($actor, $organization, $uri, $stale)->assertConflict();

        $duplicate = $command;
        $duplicate['idempotency_key'] = (string) Str::uuid();
        $duplicate['accounting_reference'] = 'S090-CORRECTION-002';
        $this->postAs($actor, $organization, $uri, $duplicate)->assertConflict();

        $foreign = $command;
        $foreign['idempotency_key'] = (string) Str::uuid();
        $this->postAs($foreignActor, $foreignOrganization, $uri, $foreign)->assertNotFound();

        self::assertSame($executionSnapshot, $execution->fresh()?->getAttributes());
        self::assertSame($debitSnapshot, $debit->fresh()?->getAttributes());
        self::assertSame($creditSnapshot, $credit->fresh()?->getAttributes());
        self::assertSame($reversalSnapshot, $reversal->fresh()?->getAttributes());
    }

    /** @param array<string, mixed> $command */
    private function postAs(User $actor, Organization $organization, string $uri, array $command): TestResponse
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->unsetRelation('permissions');

        return $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri, $command);
    }

    private function actorFor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function createEntry(FinancialSettlementAccountingPostingExecution $execution, Organization $organization, int $sequence, string $side, string $account, int $amount): FinancialSettlementAccountingPostingEntry
    {
        $entry = new FinancialSettlementAccountingPostingEntry;
        $entry->fill($this->attributesFor($entry, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->id,
            'sequence_number' => $sequence,
            'side' => $side,
            'account_code' => $account,
            'amount_minor' => $amount,
            'currency' => 'CZK',
        ]));
        $entry->saveOrFail();

        return $entry;
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function attributesFor(Model $model, array $overrides): array
    {
        $attributes = [];
        foreach ($model->getFillable() as $column) {
            if (array_key_exists($column, $overrides)) {
                $attributes[$column] = $overrides[$column];
            } elseif ($column === 'public_id' || $column === 'idempotency_key') {
                $attributes[$column] = (string) Str::uuid();
            } elseif (str_contains($column, 'fingerprint')) {
                $attributes[$column] = hash('sha256', 's090-'.$column);
            } elseif (str_ends_with($column, '_id')) {
                $attributes[$column] = 900001;
            } elseif (str_contains($column, 'amount_minor') || str_contains($column, 'total_debit_minor') || str_contains($column, 'total_credit_minor')) {
                $attributes[$column] = 50000;
            } elseif ($column === 'currency') {
                $attributes[$column] = 'CZK';
            } elseif ($column === 'status') {
                $attributes[$column] = 'posted';
            } elseif ($column === 'direction') {
                $attributes[$column] = 'payable';
            } elseif ($column === 'revision' || $column === 'sequence_number') {
                $attributes[$column] = 1;
            } elseif (str_ends_with($column, '_date')) {
                $attributes[$column] = now()->toDateString();
            } elseif (str_ends_with($column, '_at')) {
                $attributes[$column] = now();
            } elseif (str_contains($column, 'snapshot') || $column === 'payload') {
                $attributes[$column] = ['sprint' => 'S090'];
            } elseif ($column === 'reason' || $column === 'description') {
                $attributes[$column] = 'S090 runtime fixture.';
            } elseif ($column === 'accounting_reference') {
                $attributes[$column] = 'S090-ORIGINAL';
            } elseif ($column === 'account_code') {
                $attributes[$column] = '311000';
            } elseif ($column === 'side') {
                $attributes[$column] = 'debit';
            } else {
                $attributes[$column] = 's090-'.$column;
            }
        }

        return $attributes;
    }
}
