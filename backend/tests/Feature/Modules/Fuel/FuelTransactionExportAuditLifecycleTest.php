<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Fuel\Services\FuelTransactionExportAuditService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FuelTransactionExportAuditLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_export_audit_is_normalized_and_organization_scoped(): void
    {
        $owner = Organization::query()->create(['name' => 'Owner', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $outsider = Organization::query()->create(['name' => 'Outsider', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        $service = app(FuelTransactionExportAuditService::class);

        $event = $service->recordSuccessful((int) $owner->id, (int) $actor->id, [
            'date_from' => '2026-09-01',
            'provider' => 'ORLEN',
            'driver_id' => 17,
            'card' => '7082 7491 6740 0123',
            'search' => '  Plzen  ',
            'page' => 9,
            'raw_payload' => 'forbidden',
        ], 24, '../prehled-tankovani.csv');
        $service->recordSuccessful((int) $outsider->id, (int) $actor->id, [], 99, 'outsider.csv');

        self::assertSame((int) $owner->id, $event->organization_id);
        self::assertSame(24, $event->row_count);
        self::assertSame('prehled-tankovani.csv', $event->filename);
        self::assertSame([
            'date_from' => '2026-09-01',
            'provider' => 'ORLEN',
            'driver_id' => 17,
            'card_last_four' => '0123',
            'search' => 'Plzen',
        ], $event->filters);
        self::assertArrayNotHasKey('card', $event->filters);
        self::assertArrayNotHasKey('page', $event->filters);
        self::assertArrayNotHasKey('raw_payload', $event->filters);

        $history = $service->history((int) $owner->id, ['per_page' => 15]);
        self::assertSame(1, $history['pagination']['total']);
        self::assertSame($event->public_id, $history['items'][0]['public_id']);
        self::assertSame((int) $actor->id, $history['items'][0]['exported_by']['id']);
        self::assertArrayNotHasKey('organization_id', $history['items'][0]);
    }
}
