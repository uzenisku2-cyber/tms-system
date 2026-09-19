<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversal;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingReversalEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementAccountingPostingReversalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_reversal_http_lifecycle_is_balanced_scoped_idempotent_append_only_and_non_mutating(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $organization = Organization::query()->create(['name' => 'S089 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S089 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization);
        $foreignActor = $this->actorFor($foreignOrganization);
        $this->activatePermissionTeam($organization, $actor);

        $execution = new FinancialSettlementAccountingPostingExecution;
        $execution->fill($this->attributesFor($execution, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_handoff_id' => 900001,
            'revision' => 3,
            'status' => 'executed',
            'currency' => 'CZK',
            'amount_minor' => 50000,
            'total_debit_minor' => 50000,
            'total_credit_minor' => 50000,
            'executed_by_user_id' => $actor->id,
        ]));
        $execution->saveOrFail();

        $debit = $this->createEntry($execution, $organization, 1, 'debit', '311000', 50000);
        $credit = $this->createEntry($execution, $organization, 2, 'credit', '395000', 50000);
        $executionSnapshot = $execution->fresh()?->getAttributes();
        $debitSnapshot = $debit->fresh()?->getAttributes();
        $creditSnapshot = $credit->fresh()?->getAttributes();
        self::assertIsArray($executionSnapshot);
        self::assertIsArray($debitSnapshot);
        self::assertIsArray($creditSnapshot);

        $idempotencyKey = (string) Str::uuid();
        $uri = '/api/v1/financial-settlement-accounting-posting-executions/'.$execution->public_id.'/reversal';
        $command = ['idempotency_key' => $idempotencyKey, 'expected_revision' => 3, 'reason' => 'S089 controlled reversal.'];
        $response = $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri, $command);
        $response->assertCreated();
        $publicId = (string) $response->json('data.public_id');
        self::assertNotSame('', $publicId);

        $reversal = FinancialSettlementAccountingPostingReversal::query()->where('public_id', $publicId)->firstOrFail();
        self::assertSame((int) $organization->id, (int) $reversal->owner_organization_id);
        self::assertSame((int) $execution->id, (int) $reversal->financial_settlement_accounting_posting_execution_id);
        self::assertSame(50000, (int) $reversal->total_debit_minor);
        self::assertSame(50000, (int) $reversal->total_credit_minor);
        self::assertSame(1, (int) $reversal->revision);

        $entries = FinancialSettlementAccountingPostingReversalEntry::query()->where('financial_settlement_accounting_posting_reversal_id', $reversal->id)->orderBy('sequence')->get();
        self::assertCount(2, $entries);
        self::assertSame('credit', (string) $entries[0]->entry_side);
        self::assertSame('311000', (string) $entries[0]->account_code);
        self::assertSame(50000, (int) $entries[0]->amount_minor);
        self::assertSame('debit', (string) $entries[1]->entry_side);
        self::assertSame('395000', (string) $entries[1]->account_code);
        self::assertSame(50000, (int) $entries[1]->amount_minor);
        self::assertSame(50000, (int) $entries->where('entry_side', 'debit')->sum('amount_minor'));
        self::assertSame(50000, (int) $entries->where('entry_side', 'credit')->sum('amount_minor'));
        self::assertSame(1, FinancialSettlementAccountingPostingReversalEvent::query()->where('financial_settlement_accounting_posting_reversal_id', $reversal->id)->count());

        $replay = $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri, $command);
        $replay->assertCreated()->assertJsonPath('data.public_id', $publicId);
        self::assertSame(1, FinancialSettlementAccountingPostingReversal::query()->count());
        self::assertSame(2, FinancialSettlementAccountingPostingReversalEntry::query()->count());
        self::assertSame(1, FinancialSettlementAccountingPostingReversalEvent::query()->count());

        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($uri, ['idempotency_key' => $idempotencyKey, 'expected_revision' => 3, 'reason' => 'Different command.'])
            ->assertConflict();
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($uri, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 4, 'reason' => 'Stale revision.'])
            ->assertConflict();
        $this->activatePermissionTeam($foreignOrganization, $foreignActor);
        $this->actingAs($foreignActor)->withHeader('X-Organization-ID', (string) $foreignOrganization->id)
            ->postJson($uri, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'Foreign organization.'])
            ->assertNotFound();

        self::assertSame($executionSnapshot, $execution->fresh()?->getAttributes());
        self::assertSame($debitSnapshot, $debit->fresh()?->getAttributes());
        self::assertSame($creditSnapshot, $credit->fresh()?->getAttributes());
    }

    private function activatePermissionTeam(Organization $organization, User $actor): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->unsetRelation('permissions');
    }

    private function actorFor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
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
            'sequence' => $sequence, 'sequence_number' => $sequence, 'entry_side' => $side, 'side' => $side,
            'account_code' => $account, 'amount_minor' => $amount, 'currency' => 'CZK',
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
                $attributes[$column] = hash('sha256', 's089-'.$column);
            } elseif (str_ends_with($column, '_id')) {
                $attributes[$column] = 900001;
            } elseif (str_contains($column, 'amount_minor') || str_contains($column, 'total_debit_minor') || str_contains($column, 'total_credit_minor')) {
                $attributes[$column] = 50000;
            } elseif ($column === 'currency') {
                $attributes[$column] = 'CZK';
            } elseif ($column === 'status') {
                $attributes[$column] = 'executed';
            } elseif ($column === 'revision' || $column === 'sequence') {
                $attributes[$column] = 1;
            } elseif (str_ends_with($column, '_date')) {
                $attributes[$column] = now()->toDateString();
            } elseif (str_ends_with($column, '_at')) {
                $attributes[$column] = now();
            } elseif (str_contains($column, 'snapshot') || $column === 'payload') {
                $attributes[$column] = [];
            } elseif (str_contains($column, 'account')) {
                $attributes[$column] = '395000';
            } elseif (str_contains($column, 'side')) {
                $attributes[$column] = 'debit';
            } else {
                $attributes[$column] = 's089-'.$column;
            }
        }

        return $attributes;
    }
}
