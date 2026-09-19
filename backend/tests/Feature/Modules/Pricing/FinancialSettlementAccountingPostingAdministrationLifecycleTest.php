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
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecutionEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
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

final class FinancialSettlementAccountingPostingAdministrationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_read_only_administration_lists_filters_and_presents_the_scoped_posting_lifecycle(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $organization = Organization::query()->create(['name' => 'S091 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S091 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization);
        $foreignActor = $this->actorFor($foreignOrganization);

        $handoff = $this->createModel(FinancialSettlementAccountingPostingHandoff::class, [
            'owner_organization_id' => $organization->id,
            'status' => 'prepared',
            'revision' => 2,
            'amount_minor' => 50000,
            'currency' => 'CZK',
        ]);
        $execution = $this->createModel(FinancialSettlementAccountingPostingExecution::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_handoff_id' => $handoff->getKey(),
            'status' => 'posted',
            'revision' => 3,
            'posting_date' => now()->toDateString(),
            'accounting_reference' => 'S091-POSTING-001',
            'amount_minor' => 50000,
            'currency' => 'CZK',
            'direction' => 'payable',
            'executed_by_user_id' => $actor->id,
            'executed_at' => now()->subMinutes(3),
        ]);
        $debit = $this->createModel(FinancialSettlementAccountingPostingEntry::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->getKey(),
            'sequence_number' => 1,
            'side' => 'debit',
            'account_code' => '311000',
            'amount_minor' => 50000,
            'currency' => 'CZK',
        ]);
        $credit = $this->createModel(FinancialSettlementAccountingPostingEntry::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->getKey(),
            'sequence_number' => 2,
            'side' => 'credit',
            'account_code' => '395000',
            'amount_minor' => 50000,
            'currency' => 'CZK',
        ]);
        $this->createModel(FinancialSettlementAccountingPostingExecutionEvent::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->getKey(),
            'event_type' => 'posting_executed',
            'revision' => 3,
            'occurred_at' => now()->subMinutes(3),
        ]);

        $reversal = $this->createModel(FinancialSettlementAccountingPostingReversal::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_execution_id' => $execution->getKey(),
            'status' => 'reversed',
            'revision' => 1,
            'total_debit_minor' => 50000,
            'total_credit_minor' => 50000,
            'reversed_by_user_id' => $actor->id,
            'reversed_at' => now()->subMinutes(2),
            'reason' => 'S091 controlled reversal.',
        ]);
        $this->createModel(FinancialSettlementAccountingPostingReversalEntry::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_reversal_id' => $reversal->getKey(),
            'sequence' => 1,
            'entry_side' => 'credit',
            'account_code' => '311000',
            'amount_minor' => 50000,
            'currency' => 'CZK',
        ]);
        $this->createModel(FinancialSettlementAccountingPostingReversalEvent::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_reversal_id' => $reversal->getKey(),
            'event_type' => 'posting_reversed',
            'revision' => 1,
            'occurred_at' => now()->subMinutes(2),
        ]);

        $replacement = $this->createModel(FinancialSettlementAccountingPostingExecution::class, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_handoff_id' => null,
            'status' => 'posted',
            'revision' => 1,
            'posting_date' => now()->toDateString(),
            'accounting_reference' => 'S091-POSTING-001-R1',
            'amount_minor' => 50000,
            'currency' => 'CZK',
            'direction' => 'payable',
            'executed_by_user_id' => $actor->id,
            'executed_at' => now()->subMinute(),
        ]);
        $correction = $this->createModel(FinancialSettlementAccountingPostingCorrection::class, [
            'owner_organization_id' => $organization->id,
            'original_execution_id' => $execution->getKey(),
            'reversal_id' => $reversal->getKey(),
            'replacement_execution_id' => $replacement->getKey(),
            'revision' => 1,
            'corrected_by_user_id' => $actor->id,
            'corrected_at' => now()->subMinute(),
            'reason' => 'S091 controlled correction.',
        ]);
        $this->createModel(FinancialSettlementAccountingPostingCorrectionEvent::class, [
            'owner_organization_id' => $organization->id,
            'correction_id' => $correction->getKey(),
            'event_type' => 'posting_corrected',
            'revision' => 1,
            'occurred_at' => now()->subMinute(),
        ]);

        $snapshots = collect([$handoff, $execution, $debit, $credit, $reversal, $replacement, $correction])
            ->mapWithKeys(fn (Model $model): array => [$model::class.'#'.$model->getKey() => $model->fresh()?->getAttributes()])
            ->all();

        $this->useOrganization($organization, $actor);
        $index = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/financial-settlement-accounting-postings?status=posted&currency=CZK&accounting_reference=S091-POSTING&from_date='.now()->toDateString().'&to_date='.now()->toDateString());
        $index->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');
        self::assertEqualsCanonicalizing(
            [(string) $execution->public_id, (string) $replacement->public_id],
            collect($index->json('data'))->pluck('public_id')->all(),
        );

        $empty = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/financial-settlement-accounting-postings?currency=EUR');
        $empty->assertOk()->assertJsonPath('meta.total', 0)->assertJsonCount(0, 'data');

        $detail = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/financial-settlement-accounting-postings/'.$execution->public_id);
        $detail->assertOk()
            ->assertJsonPath('data.public_id', (string) $execution->public_id)
            ->assertJsonPath('data.handoff.public_id', (string) $handoff->public_id)
            ->assertJsonPath('data.reversal.public_id', (string) $reversal->public_id)
            ->assertJsonPath('data.correction.public_id', (string) $correction->public_id)
            ->assertJsonPath('data.correction.original_execution_public_id', (string) $execution->public_id)
            ->assertJsonPath('data.correction.replacement_execution_public_id', (string) $replacement->public_id)
            ->assertJsonCount(2, 'data.entries');
        $entries = collect($detail->json('data.entries'));
        self::assertSame(50000, (int) $entries->where('side', 'debit')->sum('amount_minor'));
        self::assertSame(50000, (int) $entries->where('side', 'credit')->sum('amount_minor'));
        self::assertEqualsCanonicalizing(['execution', 'reversal', 'correction'], collect($detail->json('data.timeline'))->pluck('stage')->unique()->values()->all());

        $this->useOrganization($foreignOrganization, $foreignActor);
        $this->actingAs($foreignActor)
            ->withHeader('X-Organization-ID', (string) $foreignOrganization->id)
            ->getJson('/api/v1/financial-settlement-accounting-postings/'.$execution->public_id)
            ->assertNotFound();

        foreach ([$handoff, $execution, $debit, $credit, $reversal, $replacement, $correction] as $model) {
            self::assertSame($snapshots[$model::class.'#'.$model->getKey()], $model->fresh()?->getAttributes());
        }
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
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function useOrganization(Organization $organization, User $actor): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->unsetRelation('permissions');
    }

    /** @param class-string<Model> $modelClass @param array<string, mixed> $overrides */
    private function createModel(string $modelClass, array $overrides): Model
    {
        $model = new $modelClass;
        $model->fill($this->attributesFor($model, $overrides));
        $model->saveOrFail();

        return $model;
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
                $attributes[$column] = hash('sha256', 's091-'.$column.'-'.Str::uuid());
            } elseif (str_ends_with($column, '_id')) {
                $attributes[$column] = 900001;
            } elseif (str_contains($column, 'amount_minor') || str_contains($column, 'total_debit_minor') || str_contains($column, 'total_credit_minor')) {
                $attributes[$column] = 50000;
            } elseif ($column === 'currency') {
                $attributes[$column] = 'CZK';
            } elseif ($column === 'status') {
                $attributes[$column] = 'prepared';
            } elseif ($column === 'revision' || $column === 'sequence' || $column === 'sequence_number' || $column === 'handoff_revision') {
                $attributes[$column] = 1;
            } elseif ($column === 'posting_date' || str_ends_with($column, '_date')) {
                $attributes[$column] = now()->toDateString();
            } elseif (str_ends_with($column, '_at')) {
                $attributes[$column] = now();
            } elseif (str_contains($column, 'snapshot') || $column === 'payload') {
                $attributes[$column] = ['source' => 's091-runtime'];
            } elseif ($column === 'direction') {
                $attributes[$column] = 'payable';
            } elseif ($column === 'side' || $column === 'entry_side') {
                $attributes[$column] = 'debit';
            } elseif ($column === 'account_code') {
                $attributes[$column] = '311000';
            } elseif ($column === 'event_type' || $column === 'type') {
                $attributes[$column] = 'created';
            } elseif ($column === 'accounting_reference') {
                $attributes[$column] = 'S091-REFERENCE';
            } elseif ($column === 'reason' || $column === 'description') {
                $attributes[$column] = 'S091 runtime fixture.';
            } else {
                $attributes[$column] = 's091-'.$column;
            }
        }

        return $attributes;
    }
}
