<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CarrierProvisionalRemunerationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_carrier_receives_only_own_provisional_remuneration(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $other = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $manager = User::factory()->create();
        $driverUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->member($carrier, $manager);
        $this->seed(RolePermissionSeeder::class);
        $this->role($carrier, $manager, 'carrier-admin');
        $this->relationship($master, $carrier);
        $this->relationship($master, $other);
        $this->report($master, $carrier, $driverUser, 10);
        $this->report($master, $other, $otherUser, 900);
        $report = DailyReport::query()->where('performed_by_driver_id', Driver::query()->where('user_id', $driverUser->getKey())->value('id'))->firstOrFail();
        $report->update(['loaded_parcels' => 50, 'redirected_parcels' => 2,
            'actual_km' => '31.50', 'surcharge_amount' => '150.00']);
        $list = $this->priceList($master, $carrier, $manager);
        $version = $list->versions()->firstOrFail();
        foreach (['delivered_parcels' => ['33.0000', 'parcel'],
            'redirected_parcels' => ['15.0000', 'parcel'],
            'undelivered_parcels' => ['0.0000', 'parcel'],
            'actual_km' => ['4.0000', 'km']] as $code => [$rate, $unit]) {
            DB::table('price_list_items')->insert([
                'price_list_version_id' => $version->getKey(), 'code' => $code,
                'calculation_method' => 'quantity_times_rate', 'unit' => $unit,
                'unit_rate' => $rate, 'currency' => 'CZK',
                'quantity_source' => $code, 'position' => count(DB::table('price_list_items')->where('price_list_version_id', $version->getKey())->get()) + 1,
                'created_at' => now(),
            ]);
        }
        $ruleId = DB::table('price_list_conditional_rules')->insertGetId([
            'price_list_version_id' => $version->getKey(),
            'code' => 'delivery_quality', 'name' => 'Quality',
            'metric_type' => 'ratio_percentage',
            'metric_numerator_source' => 'delivered_parcels',
            'metric_denominator_source' => 'loaded_parcels',
            'evaluation_scope' => 'monthly_price_list',
            'reward_method' => 'amount_per_unit',
            'reward_quantity_source' => 'delivered_parcels',
            'position' => 1, 'created_at' => now(),
        ]);
        DB::table('price_list_conditional_bands')->insert([
            'price_list_conditional_rule_id' => $ruleId,
            'minimum_value' => '20.0000', 'maximum_value' => '100.0000',
            'minimum_inclusive' => true, 'maximum_inclusive' => true,
            'adjustment_value' => '4.0000', 'position' => 1,
            'created_at' => now(),
        ]);
        foreach ([
            ['numerator', 'delivered_parcels', 1],
            ['numerator', 'redirected_parcels', 2],
            ['numerator', 'customer_rejected_parcels', 3],
            ['denominator', 'loaded_parcels', 1],
        ] as [$role, $source, $position]) {
            DB::table('price_list_conditional_rule_metric_components')->insert([
                'price_list_conditional_rule_id' => $ruleId,
                'component_role' => $role, 'metric_source' => $source,
                'position' => $position, 'created_at' => now(),
            ]);
        }
        foreach (['delivered_parcels', 'redirected_parcels'] as $position => $source) {
            DB::table('price_list_conditional_rule_reward_components')->insert([
                'price_list_conditional_rule_id' => $ruleId,
                'metric_source' => $source, 'position' => $position + 1,
                'created_at' => now(),
            ]);
        }
        Sanctum::actingAs($manager);
        $this->withHeaders(['X-Organization-ID' => (string) $carrier->getKey()])
            ->getJson('/api/v1/carrier/provisional-remuneration')
            ->assertOk()->assertJsonPath('data.route_count', 1)
            ->assertJsonPath('data.unpriced_route_count', 0)
            ->assertJsonPath('data.routes.0.amounts_minor.surcharge_minor', 15000)
            ->assertJsonPath('data.routes.0.amounts_minor.quality_minor', 4800)
            ->assertJsonPath('data.total_minor', 68400);
        DailyReport::query()->create([
            'organization_id' => $master->getKey(),
            'performed_by_driver_id' => $report->performed_by_driver_id,
            'entered_by_user_id' => $driverUser->getKey(),
            'route_number' => 'R-incomplete',
            'route_number_normalized' => 'r-incomplete',
            'service_date' => '2026-07-30',
            'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
            'entered_on_behalf' => true, 'loaded_parcels' => 10,
            'delivered_parcels' => 1, 'redirected_parcels' => 0,
            'actual_km' => null, 'current_version' => 1,
        ]);
        $this->withHeaders(['X-Organization-ID' => (string) $carrier->getKey()])
            ->getJson('/api/v1/carrier/provisional-remuneration')
            ->assertOk()->assertJsonPath('data.unpriced_route_count', 1)
            ->assertJsonPath('data.total_minor', null)
            ->assertJsonPath('data.routes.0.quality_pending', true)
            ->assertJsonPath('data.routes.0.amounts_minor.quality_minor', 0);
        $this->withHeaders(['X-Organization-ID' => (string) $other->getKey()])
            ->getJson('/api/v1/carrier/provisional-remuneration')->assertForbidden();
    }

    private function organization(string $type): Organization
    {
        return Organization::query()->create([
            'name' => 'Org '.fake()->uuid(), 'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $user): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
    }

    private function role(Organization $organization, User $user, string $name): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->getKey());
        $user->assignRole($name);
        $registrar->setPermissionsTeamId(null);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }

    private function relationship(Organization $master, Organization $carrier): void
    {
        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
    }

    private function report(Organization $master, Organization $carrier, User $user, int $delivered): DriverOrganizationAssignment
    {
        $driver = Driver::query()->create([
            'user_id' => $user->getKey(), 'first_name' => 'Driver',
            'last_name' => 'Test', 'active' => true,
        ]);
        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(), 'organization_id' => $carrier->getKey(),
            'created_by_user_id' => $user->getKey(), 'valid_from' => '2026-07-01',
        ]);
        DailyReport::query()->create([
            'organization_id' => $master->getKey(),
            'performed_by_driver_id' => $driver->getKey(),
            'entered_by_user_id' => $user->getKey(),
            'route_number' => 'R-'.fake()->uuid(),
            'route_number_normalized' => 'r-'.fake()->uuid(),
            'service_date' => '2026-07-29', 'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
            'entered_on_behalf' => true, 'delivered_parcels' => $delivered,
            'current_version' => 1,
        ]);

        return $assignment;
    }

    private function priceList(Organization $master, Organization $carrier, User $actor): PriceList
    {
        $relationship = OrganizationRelationship::query()->where('source_organization_id', $master->getKey())
            ->where('target_organization_id', $carrier->getKey())->firstOrFail();
        $list = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $master->getKey(),
            'customer_organization_id' => $master->getKey(),
            'provider_organization_id' => $carrier->getKey(),
            'managed_by_organization_id' => $master->getKey(),
            'name' => 'Carrier tariff', 'currency' => 'CZK',
            'status' => 'active', 'created_by_user_id' => $actor->getKey(),
        ]);
        PriceListVersion::query()->create([
            'price_list_id' => $list->getKey(), 'version_number' => 1,
            'status' => 'active', 'valid_from' => '2026-01-01',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $actor->getKey(),
            'approved_at' => now(), 'activated_at' => now(),
        ]);

        return $list;
    }
}
