<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\DailyReports\Services\DepotWorkbookReader;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Services\FuelTransactionImportService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FuelTransactionImportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_uses_match_status_and_persists_a_matched_transaction(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Test Organization',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        $card = FuelCard::query()->create([
            'public_id' => (string) Str::uuid(),
            'owner_organization_id' => $organization->id,
            'provider' => 'ORLEN',
            'provider_card_identifier' => '7082749167400600015',
            'masked_card_number' => '**** 00015',
            'label' => 'Regression card',
            'status' => 'active',
            'valid_from' => '2025-06-01',
            'currency' => 'CZK',
            'lock_version' => 1,
            'created_by_user_id' => $actor->id,
        ]);
        $assignment = FuelCardAssignment::query()->create([
            'public_id' => (string) Str::uuid(),
            'fuel_card_id' => $card->id,
            'responsible_organization_id' => $organization->id,
            'assignment_type' => 'organization',
            'status' => 'active',
            'valid_from' => '2025-06-01 00:00:00',
            'reason' => 'Regression assignment',
            'assigned_by_user_id' => $actor->id,
        ]);

        $csv = tempnam(sys_get_temp_dir(), 'orlen-import-');
        self::assertIsString($csv);

        $encoded = '77u/xIzDrXNsbyDDusSNdGVua3k7RGF0dW0gYSDEjWFzIHByb2RlamU7xIzDrXNsbyBrYXJ0eTtSWjtKbcOpbm8gxZlpZGnEjWU7WsOha2F6bmlja8OhIHBvbG/FvmthO0xhYmVsO1R5cCB0cmFuc2FrY2U7TW5vxb5zdHbDrTtKZWRub3Rrb3bDoSBjZW5hO0plZG5vdGtvdsOhIGNlbmEgcG8gc2xldsSbO0NlbGtvdsOhIGNlbmE7Q2Vsa292w6EgY2VuYSBwbyBzbGV2xJs7U2xldmE7U2F6YmEgRFBIO0RQSDtDZWxrb3bDoSBjZW5hIChiZXogRFBIKTtNxJtuYTtTdGF2IHRhY2hvbWV0cnU7xIxlcnBhY8OtIHN0YW5pY2U7QWRyZXNhIMSNZXJwYWPDrSBzdGFuaWNlO1Byb2R1a3Q7T0JVO1ZTIHBvaGxlZMOhdmt5O0Zha3R1cmEgxI3DrXNsbzvEjMOtc2xvIHN0xZllZGlza2E7U3TFmWVkaXNrbwpURVNULVJFQ0VJUFQtMDAxOzE1LjYuMjAyNSAxMDozMDowMDs3MDgyNzQ5MTY3NDAwNjAwMDE1wqA7Ozs7O1BsYXRiYTs0MCwwMDszNSw1MDszNCw3MDsxNDIwLDAwOzEzODgsMDA7LTMyLDAwOzIxLDAwOzI0MCw5MjsxMTQ3LDA4O0NaSzs7MDAxIC0gVEVTVDtUZXN0IGFkZHJlc3M7RGllc2VsOzs7OzY3NDAwNjtUZXN0IE9yZ2FuaXphdGlvbgo=';
        $contents = base64_decode($encoded, true);
        self::assertIsString($contents);
        self::assertNotFalse(file_put_contents($csv, $contents));

        try {
            $batch = (new FuelTransactionImportService(new DepotWorkbookReader))->import(
                (int) $organization->id,
                $actor,
                'ORLEN',
                'orlen-regression.csv',
                $csv,
            );
        } finally {
            @unlink($csv);
        }

        self::assertSame('completed', $batch->status);
        self::assertSame(1, $batch->source_row_count);
        self::assertSame(1, $batch->accepted_row_count);
        self::assertSame(0, $batch->review_row_count);
        self::assertSame(1, FuelImportBatch::query()->count());

        $row = FuelImportRow::query()->sole();
        self::assertSame('accepted', $row->status);

        $transaction = FuelTransaction::query()->sole();
        self::assertSame('matched', $transaction->match_status);
        self::assertSame('provider_card_and_assignment_period', $transaction->match_method);
        self::assertSame($card->id, $transaction->fuel_card_id);
        self::assertSame($assignment->id, $transaction->fuel_card_assignment_id);
        self::assertSame($organization->id, $transaction->responsible_organization_id);
        self::assertSame($transaction->id, $row->fuel_transaction_id);
    }
}
