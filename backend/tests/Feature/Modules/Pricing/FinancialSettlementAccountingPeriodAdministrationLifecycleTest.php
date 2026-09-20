<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriod;
use App\Modules\Pricing\Models\FinancialSettlementAccountingPeriodEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementAccountingPeriodAdministrationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_administration_lists_filters_and_shows_only_organization_scoped_period_history_without_mutation(): void
    {
        $organization = Organization::query()->create(['name' => 'S093 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $foreignOrganization = Organization::query()->create(['name' => 'S093 foreign', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actorFor($organization);
        $foreignActor = $this->actorFor($foreignOrganization);

        $period = FinancialSettlementAccountingPeriod::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $organization->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'currency' => 'CZK',
            'status' => FinancialSettlementAccountingPeriod::STATUS_CLOSED,
            'revision' => 2,
            'closed_by_user_id' => $actor->id,
            'closed_at' => now(),
            'last_reason' => 'S093 controlled close.',
        ]);
        FinancialSettlementAccountingPeriodEvent::query()->create([
            'accounting_period_id' => $period->getKey(),
            'event_type' => 'accounting_period_opened',
            'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', 's093-open'),
            'payload' => ['reason' => 'S093 opened.'],
            'actor_user_id' => $actor->id,
            'occurred_at' => now()->subMinute(),
            'revision' => 1,
        ]);
        FinancialSettlementAccountingPeriodEvent::query()->create([
            'accounting_period_id' => $period->getKey(),
            'event_type' => 'accounting_period_closed',
            'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', 's093-close'),
            'payload' => ['reason' => 'S093 controlled close.'],
            'actor_user_id' => $actor->id,
            'occurred_at' => now(),
            'revision' => 2,
        ]);
        FinancialSettlementAccountingPeriod::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $foreignOrganization->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'currency' => 'CZK',
            'status' => FinancialSettlementAccountingPeriod::STATUS_OPEN,
            'revision' => 1,
            'last_reason' => 'Foreign period.',
        ]);

        $periodSnapshot = $period->fresh()?->getAttributes();
        $eventSnapshots = FinancialSettlementAccountingPeriodEvent::query()
            ->where('accounting_period_id', $period->getKey())
            ->orderBy('revision')
            ->get()
            ->map(fn (FinancialSettlementAccountingPeriodEvent $event): array => $event->getAttributes())
            ->all();

        $this->useOrganization($organization, $actor);
        $index = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/financial-settlement-accounting-periods?status=closed&currency=czk&from_date=2026-09-15&to_date=2026-09-15');
        $index->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', (string) $period->getAttribute('public_id'))
            ->assertJsonPath('data.0.event_count', 2);

        $detail = $this->actingAs($actor)
            ->withHeader('X-Organization-ID', (string) $organization->id)
            ->getJson('/api/v1/financial-settlement-accounting-periods/'.$period->getAttribute('public_id'));
        $detail->assertOk()
            ->assertJsonPath('data.status', FinancialSettlementAccountingPeriod::STATUS_CLOSED)
            ->assertJsonPath('data.revision', 2)
            ->assertJsonCount(2, 'data.events')
            ->assertJsonPath('data.events.1.event_type', 'accounting_period_closed');

        $this->useOrganization($foreignOrganization, $foreignActor);
        $this->actingAs($foreignActor)
            ->withHeader('X-Organization-ID', (string) $foreignOrganization->id)
            ->getJson('/api/v1/financial-settlement-accounting-periods/'.$period->getAttribute('public_id'))
            ->assertNotFound();

        self::assertSame($periodSnapshot, $period->fresh()?->getAttributes());
        self::assertSame($eventSnapshots, FinancialSettlementAccountingPeriodEvent::query()
            ->where('accounting_period_id', $period->getKey())
            ->orderBy('revision')
            ->get()
            ->map(fn (FinancialSettlementAccountingPeriodEvent $event): array => $event->getAttributes())
            ->all());
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
}
