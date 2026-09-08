<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Models\BankStatementImportRow;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\CsobBankStatementCsvAdapter;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CsobBankStatementImportApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_explicit_csob_adapter_imports_synthetic_export_without_financial_side_effects(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'S069 CSOB API organization',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
        ]);
        $this->grant($actor, $organization);
        Sanctum::actingAs($actor);

        $fixture = file_get_contents(base_path('tests/Fixtures/Fleet/csob-bank-statement-synthetic.csv'));
        self::assertIsString($fixture);

        $response = $this->withHeader('X-Organization-ID', (string) $organization->id)
            ->post('/api/v1/bank-statement-imports', [
                'idempotency_key' => (string) Str::uuid(),
                'adapter' => 'csob_csv',
                'file' => UploadedFile::fake()->createWithContent('csob.csv', $fixture),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.source_type', 'csob_csv')
            ->assertJsonPath('data.parser_version', CsobBankStatementCsvAdapter::VERSION)
            ->assertJsonPath('data.accepted_row_count', 2)
            ->assertJsonPath('data.duplicate_candidate_row_count', 0)
            ->assertJsonPath('data.rejected_row_count', 0);

        $batch = BankStatementImportBatch::query()->sole();
        self::assertSame('csob_csv', $batch->source_type);
        self::assertSame(CsobBankStatementCsvAdapter::VERSION, $batch->mapping_version);
        self::assertSame(';', $batch->delimiter);
        self::assertSame('UTF-8', $batch->encoding);
        self::assertSame([], $batch->mapping);

        $rows = BankStatementImportRow::query()->orderBy('source_row')->get();
        self::assertCount(2, $rows);
        self::assertSame([4, 5], $rows->pluck('source_row')->all());
        self::assertSame(['accepted', 'accepted'], $rows->pluck('status')->all());
        self::assertSame(2, BankTransactionEvidence::query()->count());
        self::assertTrue(BankTransactionEvidence::query()->get()->every(
            static fn (BankTransactionEvidence $evidence): bool => str_starts_with((string) $evidence->source_reference, 'bank-import:csob:')
        ));

        self::assertDatabaseCount('bank_statement_import_duplicate_candidates', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }

    private function grant(User $actor, Organization $organization): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId((int) $organization->id);
            $registrar->forgetCachedPermissions();
            $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }
}
