<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardAssignment;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelImportRowCorrection;
use App\Modules\Fuel\Models\FuelImportRowFinalization;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FuelImportFinalizationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_latest_correction_finalizes_existing_review_transaction_once_and_audits_snapshots(): void
    {
        [$actor, $organization] = $this->context();
        $this->grant($actor, $organization, ['compensation.manage', 'compensation.view']);
        $this->cardAndAssignment($actor, $organization, 'CARD-001');
        [$batch, $row, $transaction] = $this->reviewRow($actor, $organization, 'CARD-001');
        $correction = $this->correction($actor, $row, 1, $this->payload('CARD-001', '45.250000'));
        Sanctum::actingAs($actor);

        $url = '/api/v1/fuel-imports/'.$batch->public_id.'/rows/7/finalization';
        $this->organizationRequest($organization)->postJson($url, [
            'expected_correction_revision' => 1,
            'reason' => 'DispeÄŤer ovÄ›Ĺ™il opravenĂ© hodnoty proti dokladu dodavatele.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.correction_revision', 1)
            ->assertJsonPath('data.transaction_public_id', $transaction->public_id);

        $row->refresh();
        $transaction->refresh();
        $batch->refresh();
        self::assertSame('accepted', $row->status);
        self::assertSame($transaction->id, $row->fuel_transaction_id);
        self::assertSame('matched', $transaction->match_status);
        self::assertSame('provider_card_and_assignment_period', $transaction->match_method);
        self::assertSame('45.250000', $transaction->quantity);
        self::assertSame('completed', $batch->status);
        self::assertSame(1, $batch->accepted_row_count);
        self::assertSame(0, $batch->review_row_count);

        $audit = FuelImportRowFinalization::query()->sole();
        self::assertSame($row->id, $audit->fuel_import_row_id);
        self::assertSame($correction->id, $audit->fuel_import_row_correction_id);
        self::assertSame($transaction->id, $audit->fuel_transaction_id);
        self::assertSame('review', $audit->from_status);
        self::assertSame('accepted', $audit->to_status);
        self::assertSame('review', $audit->before_snapshot['match_status']);
        self::assertSame('matched', $audit->after_snapshot['match_status']);

        $this->organizationRequest($organization)->postJson($url, [
            'expected_correction_revision' => 1,
            'reason' => 'OpakovanĂ˝ pokus nesmĂ­ vytvoĹ™it dalĹˇĂ­ audit ani transakci.',
        ])->assertUnprocessable()->assertJsonValidationErrors('source_row');

        self::assertSame(1, FuelImportRowFinalization::query()->count());
        self::assertSame(1, FuelTransaction::query()->count());
    }

    public function test_stale_revision_and_unresolved_matching_are_rejected_without_side_effects(): void
    {
        [$actor, $organization] = $this->context();
        $this->grant($actor, $organization, ['compensation.manage']);
        [$batch, $row] = $this->reviewRow($actor, $organization, 'UNKNOWN-CARD');
        $this->correction($actor, $row, 1, $this->payload('UNKNOWN-CARD', '10.000000'));
        $this->correction($actor, $row, 2, $this->payload('UNKNOWN-CARD', '11.000000'));
        Sanctum::actingAs($actor);
        $url = '/api/v1/fuel-imports/'.$batch->public_id.'/rows/7/finalization';

        $this->organizationRequest($organization)->postJson($url, [
            'expected_correction_revision' => 1,
            'reason' => 'Klient se pokouĹˇĂ­ potvrdit jiĹľ zastaralou korekÄŤnĂ­ revizi.',
        ])->assertUnprocessable()->assertJsonValidationErrors('expected_correction_revision');

        $this->organizationRequest($organization)->postJson($url, [
            'expected_correction_revision' => 2,
            'reason' => 'NeznĂˇmĂˇ karta zatĂ­m nemĂˇ jednoznaÄŤnĂ© platnĂ© pĹ™iĹ™azenĂ­.',
        ])->assertUnprocessable()->assertJsonValidationErrors('corrected_payload.provider_card_identifier');

        self::assertSame(0, FuelImportRowFinalization::query()->count());
        self::assertSame('review', $row->fresh()->status);
        self::assertSame('review', FuelTransaction::query()->sole()->match_status);
        self::assertSame('completed_with_review', $batch->fresh()->status);
    }

    private function context(): array
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create(['name' => 'KĂ¶kĂ¶rÄŤenĂ˝', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);

        return [$actor, $organization];
    }

    private function cardAndAssignment(User $actor, Organization $organization, string $identifier): void
    {
        $card = FuelCard::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN',
            'provider_card_identifier' => $identifier, 'masked_card_number' => '****0001', 'label' => 'Test card', 'status' => 'active',
            'valid_from' => '2025-01-01', 'currency' => 'CZK', 'lock_version' => 1, 'created_by_user_id' => $actor->id,
        ]);
        FuelCardAssignment::query()->create([
            'public_id' => (string) Str::uuid(), 'fuel_card_id' => $card->id, 'responsible_organization_id' => $organization->id,
            'assignment_type' => 'organization', 'status' => 'active', 'valid_from' => '2025-01-01 00:00:00',
            'reason' => 'Test assignment', 'assigned_by_user_id' => $actor->id,
        ]);
    }

    private function reviewRow(User $actor, Organization $organization, string $cardIdentifier): array
    {
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'status' => 'completed_with_review',
            'original_filename' => 'orlen.csv', 'file_sha256' => hash('sha256', (string) Str::uuid()), 'schema_fingerprint' => hash('sha256', 'schema'),
            'source_row_count' => 1, 'review_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now(),
        ]);
        $payload = $this->payload($cardIdentifier, '40.000000');
        $transaction = FuelTransaction::query()->create([
            ...$payload, 'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN',
            'transaction_fingerprint' => hash('sha256', (string) Str::uuid()), 'match_status' => 'review', 'match_method' => 'unknown_card',
            'fuel_import_batch_id' => $batch->id, 'source_row' => 7,
        ]);
        $row = FuelImportRow::query()->create([
            'fuel_import_batch_id' => $batch->id, 'source_row' => 7, 'status' => 'review', 'row_fingerprint' => hash('sha256', (string) Str::uuid()),
            'provider_transaction_identifier' => $payload['provider_transaction_identifier'], 'raw_payload' => $payload,
            'normalized_payload' => $payload, 'validation_messages' => ['matching' => ['review']], 'fuel_transaction_id' => $transaction->id,
        ]);

        return [$batch, $row, $transaction];
    }

    private function correction(User $actor, FuelImportRow $row, int $revision, array $payload): FuelImportRowCorrection
    {
        return FuelImportRowCorrection::query()->create([
            'public_id' => (string) Str::uuid(), 'fuel_import_row_id' => $row->id, 'revision' => $revision,
            'original_payload' => $row->normalized_payload, 'corrected_payload' => $payload,
            'reason' => 'AuditovanĂˇ korekce importovanĂ©ho Ĺ™Ăˇdku.', 'corrected_by_user_id' => $actor->id,
        ]);
    }

    private function payload(string $cardIdentifier, string $quantity): array
    {
        return [
            'provider_transaction_identifier' => 'RECEIPT-001', 'occurred_at' => '2026-08-20 10:00:00', 'posting_date' => null,
            'provider_card_identifier' => $cardIdentifier, 'station_identifier' => 'ST-01', 'station_name' => 'Test station', 'station_address' => null,
            'product_code' => 'DIESEL', 'product_name' => 'Diesel', 'quantity' => $quantity, 'unit_of_measure' => 'L', 'unit_price' => '35.000000',
            'net_amount' => '1309.917355', 'tax_amount' => '275.082645', 'gross_amount' => '1585.000000', 'discount_amount' => null,
            'tax_rate' => '21.0000', 'currency' => 'CZK', 'vehicle_registration' => null, 'odometer' => null,
            'invoice_reference' => null, 'source_description' => 'Test import',
        ];
    }

    private function grant(User $actor, Organization $organization, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId((int) $organization->id);
            $registrar->forgetCachedPermissions();
            foreach ($permissions as $permission) {
                $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }

    private function organizationRequest(Organization $organization): static
    {
        return $this->withHeader('X-Organization-ID', (string) $organization->id);
    }
}
