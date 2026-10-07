<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialMutualCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialMutualChargeOffsetConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_carrier_accepts_exact_fuel_charge_and_cannot_decide_for_another_carrier(): void
    {
        $owner = $this->organization('Master');
        $carrier = $this->organization('Carrier');
        $other = $this->organization('Other');
        $actor = User::factory()->create();
        $charge = $this->charge($owner, $actor, $carrier->id, null, 'fuel');
        $command = $this->command('accepted');

        $this->authenticate($actor, $other, true);
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $command)->assertNotFound();
        $this->authenticate($actor, $carrier, true);
        $this->getJson('/api/v1/counterparty/offset-charges')->assertOk()->assertJsonPath('data.0.public_id', $charge->public_id);
        $this->getJson('/api/v1/counterparty/offset-charges')->assertJsonPath('data.0.source_snapshot.amount_minor', 125000);
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $command)
            ->assertCreated()->assertJsonPath('data.decision', 'accepted');
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $command)->assertOk();
        $changed = $command;
        $changed['decision'] = 'rejected';
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $changed)->assertUnprocessable();
        self::assertDatabaseCount('financial_mutual_charge_offset_consents', 1);
    }

    public function test_employee_driver_may_reject_own_vehicle_cost_but_master_and_other_driver_cannot_decide(): void
    {
        $owner = $this->organization('Master');
        $masterActor = User::factory()->create();
        $driverActor = User::factory()->create();
        $otherActor = User::factory()->create();
        $driver = Driver::query()->create(['user_id' => $driverActor->id, 'first_name' => 'Employee', 'last_name' => 'One', 'active' => true]);
        $charge = $this->charge($owner, $masterActor, null, $driver->id, 'vehicle_cost');

        $this->authenticate($masterActor, $owner, true);
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $this->command('accepted'))->assertNotFound();
        $this->authenticate($otherActor, $owner, false);
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $this->command('accepted'))->assertNotFound();
        $this->authenticate($driverActor, $owner, false);
        $this->getJson('/api/v1/counterparty/offset-charges')->assertOk()->assertJsonPath('data.0.public_id', $charge->public_id);
        $this->postJson('/api/v1/counterparty/offset-charges/'.$charge->public_id.'/decision', $this->command('rejected'))
            ->assertCreated()->assertJsonPath('data.decision', 'rejected');
        self::assertDatabaseCount('financial_mutual_charge_offset_consents', 1);
    }

    private function command(string $decision): array
    {
        return ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2, 'decision' => $decision, 'reason' => 'Checked source evidence.'];
    }

    private function authenticate(User $actor, Organization $organization, bool $manager): void
    {
        OrganizationMembership::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $actor->id],
            ['relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()],
        );
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        if ($manager) {
            $actor->givePermissionTo(Permission::findOrCreate('people.manage', 'web'));
        }
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $organization->id);
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
    }

    private function charge(Organization $owner, User $ownerActor, ?int $partyOrganization, ?int $partyDriver, string $category): FinancialMutualCharge
    {
        return FinancialMutualCharge::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'counterparty_type' => $partyOrganization === null ? 'driver' : 'organization',
            'counterparty_organization_id' => $partyOrganization, 'counterparty_driver_id' => $partyDriver,
            'direction' => 'receivable', 'category' => $category, 'description' => 'Specific cost offset.',
            'service_period_from' => '2026-08-01', 'service_period_until' => '2026-08-31',
            'amount_minor' => 125000, 'currency' => 'CZK', 'vat_treatment' => 'standard',
            'offset_eligible' => true, 'status' => 'confirmed', 'visibility_status' => 'shared',
            'source_type' => 'test_source', 'source_public_id' => (string) Str::uuid(),
            'source_snapshot' => ['amount_minor' => 125000], 'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => str_repeat('a', 64), 'revision' => 2,
            'created_by_user_id' => $ownerActor->id, 'confirmed_by_user_id' => $ownerActor->id,
            'confirmed_at' => now(), 'shared_at' => now(),
        ]);
    }
}
