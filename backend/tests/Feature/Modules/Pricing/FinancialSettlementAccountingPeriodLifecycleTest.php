<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriodEvent;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPostingExecution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class FinancialSettlementAccountingPeriodLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_period_http_lifecycle_scopes_commands_locks_canonical_postings_and_preserves_compensating_replacements(): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $organization = Organization::query()->create(['name' => 'S092 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S092 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization, true);
        $unauthorizedActor = $this->actorFor($organization, false);
        $foreignActor = $this->actorFor($foreignOrganization, true);
        $uri = '/api/v1/financial-settlement-accounting-periods';
        $createKey = (string) Str::uuid();
        $create = ['idempotency_key' => $createKey, 'period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'currency' => 'CZK', 'reason' => 'S092 September period.'];

        $this->useOrganization($organization);
        $response = $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri, $create);
        $response->assertCreated()->assertJsonPath('data.status', FinancialSettlementAccountingPeriod::STATUS_OPEN)->assertJsonPath('data.revision', 1);
        $publicId = (string) $response->json('data.public_id');
        self::assertNotSame('', $publicId);

        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($uri, $create)
            ->assertCreated()->assertJsonPath('data.public_id', $publicId);
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($uri, array_replace($create, ['currency' => 'EUR']))->assertConflict();

        $this->useOrganization($organization);
        $this->actingAs($unauthorizedActor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($uri, array_replace($create, ['idempotency_key' => (string) Str::uuid()]))->assertForbidden();

        $period = FinancialSettlementAccountingPeriod::query()->where('public_id', $publicId)->firstOrFail();
        $canonical = $this->execution($organization, $actor, 920001);
        $canonicalSnapshot = $canonical->fresh()?->getAttributes();
        self::assertIsArray($canonicalSnapshot);

        $closeKey = (string) Str::uuid();
        $closeUri = $uri.'/'.$publicId.'/close';
        $close = ['idempotency_key' => $closeKey, 'expected_revision' => 1, 'reason' => 'S092 controlled close.'];
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($closeUri, $close)
            ->assertOk()->assertJsonPath('data.status', FinancialSettlementAccountingPeriod::STATUS_CLOSED)->assertJsonPath('data.revision', 2);
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)->postJson($closeUri, $close)
            ->assertOk()->assertJsonPath('data.revision', 2);
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($closeUri, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1, 'reason' => 'Stale close.'])->assertConflict();

        $this->useOrganization($foreignOrganization);
        $this->actingAs($foreignActor)->withHeader('X-Organization-ID', (string) $foreignOrganization->id)
            ->postJson($closeUri, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'Foreign close.'])->assertNotFound();

        $this->useOrganization($organization);
        try {
            $this->execution($organization, $actor, 920002);
            self::fail('A canonical posting in a closed accounting period must be rejected.');
        } catch (HttpException $exception) {
            self::assertSame(409, $exception->getStatusCode());
        }

        $replacement = $this->execution($organization, $actor, null);
        self::assertNull($replacement->getAttribute('financial_settlement_accounting_posting_handoff_id'));
        self::assertSame($canonicalSnapshot, $canonical->fresh()?->getAttributes());

        $reopenUri = $uri.'/'.$publicId.'/reopen';
        $this->actingAs($actor)->withHeader('X-Organization-ID', (string) $organization->id)
            ->postJson($reopenUri, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'S092 controlled reopen.'])
            ->assertOk()->assertJsonPath('data.status', FinancialSettlementAccountingPeriod::STATUS_REOPENED)->assertJsonPath('data.revision', 3);
        $afterReopen = $this->execution($organization, $actor, 920003);
        self::assertNotNull($afterReopen->getKey());

        self::assertSame(1, FinancialSettlementAccountingPeriod::query()->count());
        self::assertSame(3, FinancialSettlementAccountingPeriodEvent::query()->where('accounting_period_id', $period->getKey())->count());
        self::assertSame(['accounting_period_opened', 'accounting_period_closed', 'accounting_period_reopened'], FinancialSettlementAccountingPeriodEvent::query()->orderBy('revision')->pluck('event_type')->all());
        $event = FinancialSettlementAccountingPeriodEvent::query()->firstOrFail();
        $this->expectException(RuntimeException::class);
        $event->forceFill(['event_type' => 'mutated'])->save();
    }

    private function actorFor(Organization $organization, bool $grant): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        if ($grant) {
            $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        }
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        return $actor;
    }

    private function useOrganization(Organization $organization): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $organization->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function execution(Organization $organization, User $actor, ?int $handoffId): FinancialSettlementAccountingPostingExecution
    {
        $execution = new FinancialSettlementAccountingPostingExecution;
        $execution->fill($this->attributesFor($execution, [
            'owner_organization_id' => $organization->id,
            'financial_settlement_accounting_posting_handoff_id' => $handoffId,
            'handoff_revision' => 1,
            'posting_date' => '2026-09-15',
            'accounting_reference' => 'S092-'.Str::uuid(),
            'amount_minor' => 50000,
            'currency' => 'CZK',
            'direction' => 'payable',
            'status' => FinancialSettlementAccountingPostingExecution::STATUS_POSTED,
            'description' => 'S092 posting.',
            'source_snapshot' => ['sprint' => 92],
            'executed_by_user_id' => $actor->id,
            'executed_at' => now(),
            'revision' => 1,
        ]));
        $execution->saveOrFail();

        return $execution;
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
                $attributes[$column] = hash('sha256', 's092-'.$column);
            }
        }

        return $attributes;
    }
}
