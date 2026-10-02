<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CarrierImportedReportAmendmentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_imported_driver_amends_own_master_report_with_audited_version_only(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $other = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $actor = User::factory()->create();
        $importer = User::factory()->create();
        $stranger = User::factory()->create();
        $this->member($carrier, $actor);
        $this->member($other, $stranger);
        $this->seed(RolePermissionSeeder::class);
        $this->role($carrier, $actor, 'driver');
        $this->role($other, $stranger, 'driver');
        OrganizationRelationship::query()->create([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
        $driver = Driver::query()->create([
            'user_id' => $actor->getKey(), 'first_name' => 'Vít',
            'last_name' => 'Řidič', 'active' => true,
        ]);
        DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $carrier->getKey(),
            'created_by_user_id' => $importer->getKey(),
            'valid_from' => '2026-07-01',
        ]);
        $report = DailyReport::query()->create([
            'organization_id' => $master->getKey(),
            'performed_by_driver_id' => $driver->getKey(),
            'entered_by_user_id' => $importer->getKey(),
            'route_number' => 'VIT-1', 'route_number_normalized' => 'vit-1',
            'service_date' => '2026-07-29', 'status' => DailyReport::STATUS_DRAFT,
            'entry_method' => DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT,
            'entered_on_behalf' => true, 'delivered_parcels' => 10,
            'current_version' => 1,
        ]);
        $url = '/api/v1/daily-reports/'.$report->getRouteKey().'/carrier-import';
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $carrier->getKey());
        $this->patchJson($url, ['expected_version' => 1, 'delivered_parcels' => 12,
            'reason' => 'Oprava počtu zásilek'])->assertOk()
            ->assertJsonPath('data.current_version', 2);
        $report->refresh();
        self::assertSame(12, (int) $report->delivered_parcels);
        self::assertSame((int) $master->getKey(), (int) $report->organization_id);
        self::assertSame((int) $importer->getKey(), (int) $report->entered_by_user_id);
        self::assertSame(DailyReport::ENTRY_METHOD_AUTHORIZED_IMPORT, $report->entry_method);
        $this->assertDatabaseHas('daily_report_versions', [
            'daily_report_id' => $report->getKey(), 'version_number' => 2,
            'created_by_user_id' => $actor->getKey(),
        ]);
        $this->assertDatabaseHas('daily_report_events', [
            'daily_report_id' => $report->getKey(),
            'acted_by_user_id' => $actor->getKey(),
        ]);
        $this->patchJson($url, ['expected_version' => 2, 'surcharge_amount' => 500])
            ->assertForbidden();
        $this->deleteJson('/api/v1/daily-reports/'.$report->getRouteKey(),
            ['expected_version' => 2])->assertNotFound();
        Sanctum::actingAs($stranger);
        $this->withHeader('X-Organization-ID', (string) $other->getKey());
        $this->patchJson($url, ['expected_version' => 2, 'delivered_parcels' => 99])
            ->assertForbidden();
    }

    private function organization(string $type): Organization
    {
        return Organization::query()->create([
            'name' => 'Organization '.fake()->uuid(), 'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $user): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
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
}
