<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliationDecision;
use App\Modules\Fuel\Models\FuelTransactionReconciliationEvaluation;
use App\Modules\Fuel\Services\FuelTransactionReconciliationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FuelTransactionReconciliationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_day_is_evaluated_and_manual_decision_is_append_only_and_revision_guarded(): void
    {
        $organization = $this->organization();
        $actor = $this->actor($organization);
        $driver = $this->driver();
        $assignment = $this->assignment($driver, $organization);
        $transaction = $this->transaction($organization, $actor, $driver);
        $immutable = $this->immutable($transaction);
        $report = DailyReport::query()->create(['public_id' => (string) Str::uuid(), 'organization_id' => $organization->id, 'performed_by_driver_id' => $driver->id, 'entered_by_user_id' => $actor->id, 'route_number' => '46', 'route_number_normalized' => '46', 'service_date' => '2026-09-01', 'status' => DailyReport::STATUS_APPROVED, 'entry_method' => DailyReport::ENTRY_METHOD_DRIVER, 'entered_on_behalf' => false, 'current_version' => 1]);
        $service = app(FuelTransactionReconciliationService::class);
        $evaluated = $service->evaluate($transaction, (int) $organization->id, $actor, 0);
        self::assertSame('matched', $evaluated['status']);
        self::assertSame('driver_day_matched', $evaluated['result_code']);
        self::assertSame(1, $evaluated['candidate_count']);
        self::assertSame(1, $evaluated['revision']);
        self::assertSame((int) $assignment->id, (int) FuelTransactionReconciliationEvaluation::query()->sole()->driver_organization_assignment_id);
        $resolved = $service->decide($transaction, (int) $organization->id, $actor, 1, 'select_daily_report', (int) $report->id, 'DispeÄŤer potvrdil konkrĂ©tnĂ­ provoznĂ­ zĂˇznam.');
        self::assertSame('resolved', $resolved['status']);
        self::assertSame(2, $resolved['revision']);
        self::assertSame((int) $report->id, $resolved['matched_daily_report_id']);
        self::assertSame(1, FuelTransactionReconciliationDecision::query()->count());
        try {
            $service->decide($transaction, (int) $organization->id, $actor, 1, 'confirm_driver_day', null, 'Stale decision.');
            self::fail('Stale revision must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }
        try {
            $service->evaluate($transaction, (int) $organization->id, $actor, 2);
            self::fail('Automatic evaluation must not overwrite a manual resolution.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('reconciliation', $exception->errors());
        }
        self::assertSame($immutable, $this->immutable($transaction->refresh()));
    }

    private function organization(): Organization
    {
        return Organization::query()->create(['name' => 'S046 carrier', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
    }

    private function actor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $organization->id, 'supervisor_user_id' => $actor->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $organization->id, 'target_driver_id' => null, 'organization_relationship_id' => null, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);

        return $actor;
    }

    private function driver(): Driver
    {
        return Driver::query()->create(['user_id' => User::factory()->create()->id, 'first_name' => 'Jan', 'last_name' => 'SrovnanĂ˝', 'license_number' => 'S046-'.Str::uuid(), 'license_category' => 'B', 'active' => true]);
    }

    private function assignment(Driver $driver, Organization $organization): DriverOrganizationAssignment
    {
        return DriverOrganizationAssignment::query()->create(['driver_id' => $driver->id, 'organization_id' => $organization->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => User::factory()->create()->id]);
    }

    private function transaction(Organization $organization, User $actor, Driver $driver): FuelTransaction
    {
        $batch = FuelImportBatch::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 's046.csv', 'file_sha256' => str_repeat('4', 64), 'schema_fingerprint' => str_repeat('6', 64), 'source_row_count' => 1, 'accepted_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now()]);

        return FuelTransaction::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_transaction_identifier' => 'S046-001', 'transaction_fingerprint' => str_repeat('A', 64), 'occurred_at' => '2026-09-01 10:00:00', 'provider_card_identifier' => 'CARD-046', 'driver_id' => $driver->id, 'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period', 'quantity' => '40.000000', 'unit_of_measure' => 'L', 'gross_amount' => '1400.000000', 'currency' => 'CZK', 'fuel_import_batch_id' => $batch->id, 'source_row' => 2]);
    }

    private function immutable(FuelTransaction $transaction): array
    {
        return array_replace($transaction->only(['provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'occurred_at', 'provider_card_identifier', 'driver_id', 'quantity', 'gross_amount', 'currency', 'fuel_import_batch_id', 'source_row']), ['occurred_at' => $transaction->getRawOriginal('occurred_at')]);
    }
}
