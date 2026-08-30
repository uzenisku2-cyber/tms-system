<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Models\DriverSupervisoryScope;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionDriverAttribution;
use App\Modules\Fuel\Services\FuelTransactionDriverAttributionService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class FuelTransactionDriverAttributionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrowed_card_driver_can_be_corrected_and_reverted_without_changing_imported_values(): void
    {
        $organization = $this->organization('Main carrier');
        $actor = $this->authorizedActor($organization);
        $cardHolder = $this->driver('Card', 'Holder');
        $actualDriver = $this->driver('Actual', 'Driver');
        $outsiderOrganization = $this->organization('Unrelated carrier');
        $outsider = $this->driver('Outside', 'Driver');
        $holderAssignment = $this->assign($cardHolder, $organization);
        $actualAssignment = $this->assign($actualDriver, $organization);
        $this->assign($outsider, $outsiderOrganization);
        $transaction = $this->transaction($organization, $actor, $cardHolder);
        $transaction->refresh();
        $immutableBefore = $this->immutableSnapshot($transaction);
        $service = app(FuelTransactionDriverAttributionService::class);

        $eligible = collect($service->eligibleDrivers($transaction, (int) $organization->id, $actor))->pluck('driver_id')->all();
        self::assertContains((int) $cardHolder->id, $eligible);
        self::assertContains((int) $actualDriver->id, $eligible);
        self::assertNotContains((int) $outsider->id, $eligible);

        $result = $service->correct($transaction, (int) $organization->id, $actor, (int) $actualDriver->id, 0, 'ĹidiÄŤ zapomnÄ›l kartu a po oznĂˇmenĂ­ pouĹľil kartu kolegy.');
        self::assertSame((int) $cardHolder->id, $result['imported_driver_id']);
        self::assertSame((int) $actualDriver->id, $result['effective_driver_id']);
        self::assertSame(1, $result['revision']);
        self::assertCount(1, $result['history']);

        $transaction->refresh();
        self::assertSame($immutableBefore, $this->immutableSnapshot($transaction));
        self::assertSame((int) $actualDriver->id, (int) $transaction->actual_driver_id);
        self::assertSame((int) $actualAssignment->id, (int) $transaction->actual_driver_organization_assignment_id);
        $event = FuelTransactionDriverAttribution::query()->sole();
        self::assertSame((int) $cardHolder->id, (int) $event->previous_driver_id);
        self::assertSame((int) $actualDriver->id, (int) $event->new_driver_id);
        self::assertSame((int) $holderAssignment->id, (int) $event->previous_driver_organization_assignment_id);

        try {
            $service->correct($transaction, (int) $organization->id, $actor, (int) $cardHolder->id, 0, 'Stale request.');
            self::fail('A stale attribution revision must fail.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('expected_revision', $exception->errors());
        }
        self::assertSame(1, FuelTransactionDriverAttribution::query()->count());

        $reverted = $service->correct($transaction->refresh(), (int) $organization->id, $actor, (int) $cardHolder->id, 1, 'NĂˇvrat ke skuteÄŤnĂ©mu Ĺ™idiÄŤi jako novĂ˝ auditnĂ­ krok.');
        self::assertSame(2, $reverted['revision']);
        self::assertCount(2, $reverted['history']);
        self::assertSame($immutableBefore, $this->immutableSnapshot($transaction->refresh()));

        try {
            $service->correct($transaction->refresh(), (int) $organization->id, $actor, (int) $outsider->id, 2, 'NepovolenĂ˝ cizĂ­ Ĺ™idiÄŤ.');
            self::fail('An unrelated carrier driver must not be selectable.');
        } catch (HttpException $exception) {
            self::assertSame(404, $exception->getStatusCode());
        }
        self::assertSame(2, FuelTransactionDriverAttribution::query()->count());
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
    }

    private function authorizedActor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01',
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $permission = Permission::findOrCreate(DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION, 'web');
        $actor->givePermissionTo($permission);
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        DriverSupervisoryScope::query()->create([
            'organization_id' => $organization->id, 'supervisor_user_id' => $actor->id,
            'scope_type' => DriverSupervisoryScope::TYPE_ORGANIZATION,
            'target_organization_id' => $organization->id, 'target_driver_id' => null,
            'organization_relationship_id' => null, 'valid_from' => '2025-01-01',
            'created_by_user_id' => $actor->id,
        ]);

        return $actor;
    }

    private function driver(string $firstName, string $lastName): Driver
    {
        $user = User::factory()->create();

        return Driver::query()->create([
            'user_id' => $user->id, 'first_name' => $firstName, 'last_name' => $lastName,
            'license_number' => 'S044-'.Str::uuid(), 'license_category' => 'B', 'active' => true,
        ]);
    }

    private function assign(Driver $driver, Organization $organization): DriverOrganizationAssignment
    {
        return DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->id, 'organization_id' => $organization->id,
            'valid_from' => '2025-01-01', 'created_by_user_id' => User::factory()->create()->id,
        ]);
    }

    private function transaction(Organization $organization, User $actor, Driver $cardHolder): FuelTransaction
    {
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => 'borrowed-card.csv',
            'file_sha256' => str_repeat('A', 64), 'schema_fingerprint' => str_repeat('B', 64),
            'source_row_count' => 1, 'accepted_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now(),
        ]);

        return FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN', 'provider_transaction_identifier' => 'BORROWED-001',
            'transaction_fingerprint' => str_repeat('C', 64), 'occurred_at' => '2026-08-15 10:30:00',
            'provider_card_identifier' => 'CARD-001', 'driver_id' => $cardHolder->id,
            'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period',
            'quantity' => '42.500000', 'unit_of_measure' => 'L', 'unit_price' => '34.000000',
            'net_amount' => '1194.214876', 'tax_amount' => '250.785124', 'gross_amount' => '1445.000000',
            'currency' => 'CZK', 'fuel_import_batch_id' => $batch->id, 'source_row' => 2,
        ]);
    }

    /** @return array<string, mixed> */
    private function immutableSnapshot(FuelTransaction $transaction): array
    {
        $fields = ['provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'occurred_at', 'provider_card_identifier', 'driver_id', 'quantity', 'unit_price', 'net_amount', 'tax_amount', 'gross_amount', 'currency', 'fuel_import_batch_id', 'source_row'];
        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $transaction->getRawOriginal($field);
        }

        return $snapshot;
    }
}
