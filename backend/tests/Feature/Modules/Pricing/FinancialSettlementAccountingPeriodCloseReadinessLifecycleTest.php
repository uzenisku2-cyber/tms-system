<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingEntry;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingHandoff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementAccountingPeriodCloseReadinessLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_readiness_blocks_pending_handoff_then_allows_balanced_close(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $organization = Organization::query()->create(['name' => 'S094 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization);
        $period = FinancialSettlementAccountingPeriod::query()->create([
            'owner_organization_id' => $organization->id, 'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'currency' => 'CZK', 'status' => FinancialSettlementAccountingPeriod::STATUS_OPEN, 'revision' => 1, 'last_reason' => 'S094 open.',
        ]);
        $handoff = FinancialSettlementAccountingPostingHandoff::query()->create([
            'owner_organization_id' => $organization->id, 'financial_settlement_bank_payment_reconciliation_id' => 940001,
            'financial_settlement_bank_payment_id' => 940001, 'financial_settlement_statement_id' => 940001,
            'bank_transaction_evidence_id' => 940001, 'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's094-handoff'),
            'reconciliation_revision' => 1, 'payment_revision' => 1, 'statement_revision' => 1, 'bank_transaction_evidence_revision' => 1,
            'posting_date' => '2026-10-15', 'amount_minor' => 94000, 'currency' => 'CZK', 'direction' => 'payable',
            'status' => FinancialSettlementAccountingPostingHandoff::STATUS_PREPARED, 'reason' => 'S094 pending.', 'source_snapshot' => [],
            'prepared_by_user_id' => $actor->id, 'prepared_at' => now(), 'revision' => 1,
        ]);

        $uri = '/api/v1/financial-settlement-accounting-periods/'.$period->public_id;
        $this->useOrganization($organization);
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->getJson($uri.'/close-readiness')
            ->assertOk()->assertJsonPath('data.ready', false)->assertJsonPath('data.summary.pending_handoff_count', 1)
            ->assertJsonPath('data.blockers.0.code', 'pending_accounting_posting_handoffs');
        $close = ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'S094 close.'];
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri.'/close', $close)->assertConflict();
        self::assertSame(FinancialSettlementAccountingPeriod::STATUS_OPEN, $period->fresh()?->status);

        $execution = FinancialSettlementAccountingPostingExecution::query()->create([
            'owner_organization_id' => $organization->id, 'financial_settlement_accounting_posting_handoff_id' => $handoff->id,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's094-execution'), 'handoff_revision' => 1,
            'posting_date' => '2026-10-15', 'amount_minor' => 94000, 'currency' => 'CZK', 'direction' => 'payable',
            'status' => FinancialSettlementAccountingPostingExecution::STATUS_POSTED, 'description' => 'S094 balanced.', 'source_snapshot' => [],
            'executed_by_user_id' => $actor->id, 'executed_at' => now(), 'revision' => 1,
        ]);
        foreach ([[1, 'debit'], [2, 'credit']] as [$sequence, $side]) {
            FinancialSettlementAccountingPostingEntry::query()->create([
                'owner_organization_id' => $organization->id, 'financial_settlement_accounting_posting_execution_id' => $execution->id,
                'sequence_number' => $sequence, 'side' => $side, 'account_code' => 'S094', 'amount_minor' => 94000,
                'currency' => 'CZK', 'description' => 'S094 entry.', 'occurred_at' => now(),
            ]);
        }

        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->getJson($uri.'/close-readiness')
            ->assertOk()->assertJsonPath('data.ready', true)->assertJsonPath('data.summary.pending_handoff_count', 0)
            ->assertJsonPath('data.summary.unbalanced_posting_execution_count', 0);
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri.'/close', $close)
            ->assertOk()->assertJsonPath('data.status', FinancialSettlementAccountingPeriod::STATUS_CLOSED)
            ->assertJsonPath('data.events.0.payload.close_readiness.ready', true);
    }

    private function actorFor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function useOrganization(Organization $organization): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
