<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fuel;

use App\Models\User;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelImportRow;
use App\Modules\Fuel\Models\FuelImportRowCorrection;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FuelImportReviewLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_problem_row_corrections_are_append_only_and_fully_audited(): void
    {
        [$actor, $organization] = $this->context('KĂ¶kĂ¶rÄŤenĂ˝');
        [$batch, $row] = $this->batchAndRow($actor, $organization, 'review');
        $this->grant($actor, $organization, ['users.manage', 'compensation.view']);
        Sanctum::actingAs($actor);
        $url = '/api/v1/fuel-imports/'.$batch->public_id.'/rows/7/corrections';

        $this->organizationRequest($organization)->postJson($url, [
            'corrected_payload' => ['quantity' => '42.500000', 'currency' => 'CZK'],
            'reason' => 'Oprava chybnÄ› naÄŤtenĂ©ho mnoĹľstvĂ­.',
        ])->assertCreated()->assertJsonPath('data.revision', 1);

        $this->organizationRequest($organization)->postJson($url, [
            'corrected_payload' => ['quantity' => '42.500000', 'currency' => 'CZK'],
            'reason' => 'Pokus o revizi bez skuteÄŤnĂ© zmÄ›ny hodnot.',
        ])->assertUnprocessable()->assertJsonValidationErrors('corrected_payload');

        self::assertSame(1, FuelImportRowCorrection::query()
            ->where('fuel_import_row_id', $row->id)
            ->count());

        $this->organizationRequest($organization)->postJson($url, [
            'corrected_payload' => ['quantity' => '43.000000', 'currency' => 'CZK'],
            'reason' => 'DruhĂˇ oprava podle dokladu dodavatele.',
        ])->assertCreated()->assertJsonPath('data.revision', 2);

        $row->refresh();
        self::assertSame(['quantity' => 'invalid'], $row->raw_payload);
        self::assertSame(['quantity' => null, 'currency' => 'CZK'], $row->normalized_payload);
        self::assertSame(2, FuelImportRowCorrection::query()->where('fuel_import_row_id', $row->id)->count());

        $this->organizationRequest($organization)
            ->getJson('/api/v1/fuel-imports/'.$batch->public_id.'/rows/7')
            ->assertOk()
            ->assertJsonPath('data.effective_payload.quantity', '43.000000')
            ->assertJsonCount(2, 'data.corrections')
            ->assertJsonPath('data.corrections.0.original_payload.quantity', null)
            ->assertJsonPath('data.corrections.1.original_payload.quantity', '42.500000');
    }

    public function test_reason_status_and_organization_boundaries_are_enforced(): void
    {
        [$actor, $organization] = $this->context('KĂ¶kĂ¶rÄŤenĂ˝');
        [$batch] = $this->batchAndRow($actor, $organization, 'accepted');
        [, $otherOrganization] = $this->context('CizĂ­ dopravce');
        $this->grant($actor, $organization, ['users.manage']);
        Sanctum::actingAs($actor);
        $url = '/api/v1/fuel-imports/'.$batch->public_id.'/rows/7/corrections';

        $this->organizationRequest($organization)->postJson($url, [
            'corrected_payload' => ['quantity' => '1.000000'],
            'reason' => 'krĂˇtkĂ©',
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->organizationRequest($organization)->postJson($url, [
            'corrected_payload' => ['quantity' => '1.000000'],
            'reason' => 'Pokus o zmÄ›nu pĹ™ijatĂ©ho Ĺ™Ăˇdku.',
        ])->assertUnprocessable()->assertJsonValidationErrors('source_row');

        $this->organizationRequest($otherOrganization)->postJson($url, [
            'corrected_payload' => ['quantity' => '1.000000'],
            'reason' => 'Pokus z kontextu jinĂ© organizace.',
        ])->assertForbidden();
    }

    private function context(string $name): array
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2025-01-01']);

        return [$actor, $organization];
    }

    private function batchAndRow(User $actor, Organization $organization, string $status): array
    {
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id, 'provider' => 'ORLEN', 'status' => 'completed_with_review',
            'original_filename' => 'orlen.csv', 'file_sha256' => hash('sha256', (string) Str::uuid()), 'schema_fingerprint' => hash('sha256', 'schema'),
            'source_row_count' => 1, 'review_row_count' => 1, 'imported_by_user_id' => $actor->id, 'completed_at' => now(),
        ]);
        $row = FuelImportRow::query()->create([
            'fuel_import_batch_id' => $batch->id, 'source_row' => 7, 'status' => $status, 'row_fingerprint' => hash('sha256', (string) Str::uuid()),
            'raw_payload' => ['quantity' => 'invalid'], 'normalized_payload' => ['quantity' => null, 'currency' => 'CZK'], 'validation_messages' => ['quantity' => ['invalid']],
        ]);

        return [$batch, $row];
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
