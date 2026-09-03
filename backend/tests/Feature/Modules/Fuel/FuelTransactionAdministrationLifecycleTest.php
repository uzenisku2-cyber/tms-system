<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Services\FuelTransactionAdministrationService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FuelTransactionAdministrationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_is_scoped_filterable_masked_and_uses_effective_driver(): void
    {
        $owner = $this->organization('Owner');
        $outsider = $this->organization('Outsider');
        $holder = $this->driver('Card', 'Holder');
        $actual = $this->driver('Actual', 'Driver');
        $actor = User::factory()->create();
        $ownerBatch = $this->batch($owner, $actor, 'A');
        $outsiderBatch = $this->batch($outsider, $actor, 'B');

        $visible = $this->transaction($owner, $ownerBatch, $holder, 'ORLEN', '2026-08-15 10:30:00', 'CARD-000044');
        $visible->forceFill(['actual_driver_id' => $actual->id, 'driver_attribution_revision' => 1])->save();
        FuelTransactionReconciliation::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $owner->id,
            'fuel_transaction_id' => $visible->id,
            'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED,
            'result_code' => 'vehicle_mismatch',
            'service_date' => '2026-08-15',
            'candidate_count' => 2,
            'revision' => 1,
        ]);
        $this->transaction($owner, $ownerBatch, $holder, 'MOL', '2026-07-01 08:00:00', 'CARD-000055');
        $this->transaction($outsider, $outsiderBatch, $holder, 'ORLEN', '2026-08-15 11:00:00', 'CARD-000066');

        $result = app(FuelTransactionAdministrationService::class)->index((int) $owner->id, [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'provider' => 'ORLEN',
            'driver_id' => (int) $actual->id,
            'card' => '0044',
            'search' => 'Plzen',
            'reconciliation_status' => 'review_required',
            'per_page' => 15,
        ]);

        self::assertSame(1, $result['pagination']['total']);
        self::assertCount(1, $result['items']);
        $item = $result['items'][0];
        self::assertSame($visible->public_id, $item['public_id']);
        self::assertSame('2026-08-15 10:30:00', $item['occurred_at']);
        self::assertSame('**** 0044', $item['masked_card']);
        self::assertSame((int) $holder->id, $item['imported_driver']['id']);
        self::assertSame((int) $actual->id, $item['actual_driver']['id']);
        self::assertSame((int) $actual->id, $item['effective_driver']['id']);
        self::assertSame(1, $item['driver_attribution_revision']);
        self::assertSame('review_required', $item['reconciliation']['status']);
        self::assertSame('vehicle_mismatch', $item['reconciliation']['result_code']);
        self::assertSame(2, $item['reconciliation']['candidate_count']);
        self::assertSame(1, $item['reconciliation']['revision']);
        self::assertArrayNotHasKey('raw_payload', $item);
        self::assertArrayNotHasKey('normalized_payload', $item);
        self::assertArrayNotHasKey('provider_card_identifier', $item);
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create([
            'name' => $name,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function driver(string $firstName, string $lastName): Driver
    {
        return Driver::query()->create([
            'user_id' => User::factory()->create()->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'license_number' => 'S045-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);
    }

    private function batch(Organization $organization, User $actor, string $suffix): FuelImportBatch
    {
        return FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN',
            'status' => 'completed',
            'original_filename' => "s045-$suffix.csv",
            'file_sha256' => str_repeat($suffix, 64),
            'schema_fingerprint' => str_repeat('F', 64),
            'source_row_count' => 2,
            'accepted_row_count' => 2,
            'imported_by_user_id' => $actor->id,
            'completed_at' => now(),
        ]);
    }

    private function transaction(
        Organization $organization,
        FuelImportBatch $batch,
        Driver $driver,
        string $provider,
        string $occurredAt,
        string $card,
    ): FuelTransaction {
        return FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $organization->id,
            'provider' => $provider,
            'provider_transaction_identifier' => (string) Str::uuid(),
            'transaction_fingerprint' => hash('sha256', $organization->id.$provider.$occurredAt.$card),
            'occurred_at' => $occurredAt,
            'provider_card_identifier' => $card,
            'driver_id' => $driver->id,
            'match_status' => 'matched',
            'match_method' => 'provider_card_and_assignment_period',
            'station_name' => 'ORLEN Plzen',
            'product_name' => 'Diesel',
            'quantity' => '42.500000',
            'unit_of_measure' => 'L',
            'gross_amount' => '1445.000000',
            'currency' => 'CZK',
            'fuel_import_batch_id' => $batch->id,
            'source_row' => 2,
        ]);
    }
}
