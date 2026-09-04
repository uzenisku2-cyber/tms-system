<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Services\DepotWorkbookReader;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionDriverAttribution;
use App\Modules\Fuel\Models\FuelTransactionExportEvent;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Models\FuelTransactionReconciliationDecision;
use App\Modules\Fuel\Models\FuelTransactionReconciliationEvaluation;
use App\Modules\Fuel\Services\FuelTransactionAdministrationService;
use App\Modules\Fuel\Services\FuelTransactionCsvExportService;
use App\Modules\Fuel\Services\FuelTransactionDriverAttributionService;
use App\Modules\Fuel\Services\FuelTransactionExportAuditService;
use App\Modules\Fuel\Services\FuelTransactionImportService;
use App\Modules\Fuel\Services\FuelTransactionReconciliationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FuelTransactionWorkflowRegressionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_change_invalidates_resolved_projection_before_reconciliation_export_and_audit(): void
    {
        $organization = Organization::query()->create(['name' => 'S052 carrier', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = $this->actor($organization);
        $importedDriver = $this->driver('Import', 'Driver');
        $actualDriver = $this->driver('Actual', 'Driver');
        $importedAssignment = $this->assignment($importedDriver, $organization);
        $actualAssignment = $this->assignment($actualDriver, $organization);
        $this->card($organization, $actor, $importedDriver);
        $transaction = $this->importTransaction($organization, $actor);
        $immutable = $this->immutable($transaction);
        $importedReport = $this->report($organization, $actor, $importedDriver, '52');
        $actualReport = $this->report($organization, $actor, $actualDriver, '53');

        $reconciliationService = app(FuelTransactionReconciliationService::class);
        $evaluated = $reconciliationService->evaluate($transaction, (int) $organization->id, $actor, 0);
        self::assertSame('matched', $evaluated['status']);
        $initialEvaluation = FuelTransactionReconciliationEvaluation::query()->sole();
        self::assertSame((int) $importedAssignment->id, (int) $initialEvaluation->driver_organization_assignment_id);
        $resolved = $reconciliationService->decide($transaction, (int) $organization->id, $actor, 1, 'select_daily_report', (int) $importedReport->id, 'Initial operational resolution.');
        self::assertSame('resolved', $resolved['status']);
        self::assertSame(2, $resolved['revision']);

        $attribution = app(FuelTransactionDriverAttributionService::class)->correct(
            $transaction,
            (int) $organization->id,
            $actor,
            (int) $actualDriver->id,
            0,
            'The imported card holder was not the actual driver.',
        );
        self::assertSame((int) $actualDriver->id, $attribution['effective_driver_id']);

        $projection = FuelTransactionReconciliation::query()->sole();
        self::assertSame(FuelTransactionReconciliation::STATUS_PENDING, $projection->status);
        self::assertNull($projection->result_code);
        self::assertNull($projection->effective_driver_id);
        self::assertNull($projection->driver_organization_assignment_id);
        self::assertSame(0, $projection->candidate_count);
        self::assertNull($projection->matched_daily_report_id);
        self::assertSame(3, $projection->revision);
        self::assertNull($projection->evaluated_at);
        self::assertNull($projection->resolved_at);
        self::assertSame(1, FuelTransactionReconciliationEvaluation::query()->count());
        self::assertSame(1, FuelTransactionReconciliationDecision::query()->count());
        self::assertSame(1, FuelTransactionDriverAttribution::query()->count());

        try {
            $reconciliationService->evaluate($transaction->refresh(), (int) $organization->id, $actor, 2);
            self::fail('The attribution change must invalidate stale reconciliation revisions.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }

        $reevaluated = $reconciliationService->evaluate($transaction->refresh(), (int) $organization->id, $actor, 3);
        self::assertSame('matched', $reevaluated['status']);
        self::assertSame(4, $reevaluated['revision']);
        self::assertSame((int) $actualDriver->id, $reevaluated['effective_driver_id']);
        $latestEvaluation = FuelTransactionReconciliationEvaluation::query()->orderByDesc('revision')->firstOrFail();
        self::assertSame((int) $actualAssignment->id, (int) $latestEvaluation->driver_organization_assignment_id);
        self::assertNull($reevaluated['matched_daily_report_id']);
        self::assertSame((int) $actualReport->id, $reevaluated['evaluations'][1]['evidence']['candidates'][0]['id']);
        self::assertSame(2, FuelTransactionReconciliationEvaluation::query()->count());
        self::assertSame(1, FuelTransactionReconciliationDecision::query()->count());
        self::assertSame($immutable, $this->immutable($transaction->refresh()));

        $filters = ['driver_id' => (int) $actualDriver->id, 'reconciliation_status' => 'matched'];
        $output = fopen('php://temp', 'w+b');
        self::assertIsResource($output);
        $rowCount = app(FuelTransactionCsvExportService::class)->write(
            app(FuelTransactionAdministrationService::class)->exportRows((int) $organization->id, $filters),
            $output,
        );
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        self::assertSame(1, $rowCount);
        self::assertIsString($csv);
        $lines = preg_split('/\r\n|\n|\r/', substr($csv, 3), -1, PREG_SPLIT_NO_EMPTY);
        self::assertIsArray($lines);
        self::assertCount(2, $lines);
        $data = str_getcsv($lines[1], ';', '"', '');
        self::assertSame('**** 0015', $data[8]);
        self::assertSame('Import Driver', $data[9]);
        self::assertSame('Actual Driver', $data[10]);
        self::assertStringNotContainsString('7082749167400600015', $csv);

        $event = app(FuelTransactionExportAuditService::class)->recordSuccessful((int) $organization->id, (int) $actor->id, $filters, $rowCount, 's052.csv');
        self::assertSame(1, $event->row_count);
        $eventFilters = $event->filters;
        self::assertIsArray($eventFilters);
        self::assertSame((int) $actualDriver->id, $eventFilters['driver_id']);
        self::assertSame('matched', $eventFilters['reconciliation_status']);
        self::assertSame(1, FuelTransactionExportEvent::query()->count());
    }

    private function actor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate(DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION, 'web'));
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create(['organization_id' => $organization->id, 'supervisor_user_id' => $actor->id, 'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION, 'target_organization_id' => $organization->id, 'target_driver_id' => null, 'organization_relationship_id' => null, 'valid_from' => '2025-01-01', 'created_by_user_id' => $actor->id]);

        return $actor;
    }

    private function driver(string $firstName, string $lastName): Driver
    {
        return Driver::query()->create(['user_id' => User::factory()->create()->id, 'first_name' => $firstName, 'last_name' => $lastName, 'license_number' => 'S052-'.Str::uuid(), 'license_category' => 'B', 'active' => true]);
    }

    private function assignment(Driver $driver, Organization $organization): DriverOrganizationAssignment
    {
        return DriverOrganizationAssignment::query()->create(['driver_id' => $driver->id, 'organization_id' => $organization->id, 'valid_from' => '2025-01-01', 'created_by_user_id' => User::factory()->create()->id]);
    }

    private function card(Organization $organization, User $actor, Driver $driver): void
    {
        $card = FuelCard::query()->create(['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'provider_card_identifier' => '7082749167400600015', 'masked_card_number' => '**** 0015', 'label' => 'S052 card', 'status' => 'active', 'valid_from' => '2025-01-01', 'currency' => 'CZK', 'lock_version' => 1, 'created_by_user_id' => $actor->id]);
        FuelCardAssignment::query()->create(['public_id' => (string) Str::uuid(), 'fuel_card_id' => $card->id, 'responsible_organization_id' => $organization->id, 'assignment_type' => 'driver', 'driver_id' => $driver->id, 'status' => 'active', 'valid_from' => '2025-01-01 00:00:00', 'reason' => 'S052 import identity', 'assigned_by_user_id' => $actor->id]);
    }

    private function importTransaction(Organization $organization, User $actor): FuelTransaction
    {
        $path = tempnam(sys_get_temp_dir(), 's052-orlen-');
        self::assertIsString($path);
        $contents = base64_decode('77u/xIzDrXNsbyDDusSNdGVua3k7RGF0dW0gYSDEjWFzIHByb2RlamU7xIzDrXNsbyBrYXJ0eTtSWjtKbcOpbm8gxZlpZGnEjWU7WsOha2F6bmlja8OhIHBvbG/FvmthO0xhYmVsO1R5cCB0cmFuc2FrY2U7TW5vxb5zdHbDrTtKZWRub3Rrb3bDoSBjZW5hO0plZG5vdGtvdsOhIGNlbmEgcG8gc2xldsSbO0NlbGtvdsOhIGNlbmE7Q2Vsa292w6EgY2VuYSBwbyBzbGV2xJs7U2xldmE7U2F6YmEgRFBIO0RQSDtDZWxrb3bDoSBjZW5hIChiZXogRFBIKTtNxJtuYTtTdGF2IHRhY2hvbWV0cnU7xIxlcnBhY8OtIHN0YW5pY2U7QWRyZXNhIMSNZXJwYWPDrSBzdGFuaWNlO1Byb2R1a3Q7T0JVO1ZTIHBvaGxlZMOhdmt5O0Zha3R1cmEgxI3DrXNsbzvEjMOtc2xvIHN0xZllZGlza2E7U3TFmWVkaXNrbwpURVNULVJFQ0VJUFQtMDAxOzE1LjYuMjAyNSAxMDozMDowMDs3MDgyNzQ5MTY3NDAwNjAwMDE1wqA7Ozs7O1BsYXRiYTs0MCwwMDszNSw1MDszNCw3MDsxNDIwLDAwOzEzODgsMDA7LTMyLDAwOzIxLDAwOzI0MCw5MjsxMTQ3LDA4O0NaSzs7MDAxIC0gVEVTVDtUZXN0IGFkZHJlc3M7RGllc2VsOzs7OzY3NDAwNjtUZXN0IE9yZ2FuaXphdGlvbgo=', true);
        self::assertIsString($contents);
        self::assertNotFalse(file_put_contents($path, $contents));
        try {
            (new FuelTransactionImportService(new DepotWorkbookReader))->import((int) $organization->id, $actor, 'ORLEN', 's052.csv', $path);
        } finally {
            @unlink($path);
        }

        return FuelTransaction::query()->sole();
    }

    private function report(Organization $organization, User $actor, Driver $driver, string $route): DailyReport
    {
        return DailyReport::query()->create(['public_id' => (string) Str::uuid(), 'organization_id' => $organization->id, 'performed_by_driver_id' => $driver->id, 'entered_by_user_id' => $actor->id, 'route_number' => $route, 'route_number_normalized' => $route, 'service_date' => '2025-06-15', 'status' => DailyReport::STATUS_APPROVED, 'entry_method' => DailyReport::ENTRY_METHOD_DRIVER, 'entered_on_behalf' => false, 'current_version' => 1]);
    }

    /** @return array<string, mixed> */
    private function immutable(FuelTransaction $transaction): array
    {
        $fields = ['provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'occurred_at', 'provider_card_identifier', 'driver_id', 'quantity', 'unit_price', 'net_amount', 'tax_amount', 'gross_amount', 'currency', 'fuel_import_batch_id', 'source_row'];
        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $transaction->getRawOriginal($field);
        }

        return $snapshot;
    }
}
