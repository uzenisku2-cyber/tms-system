<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BankStatementImportApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_authorized_multipart_import_is_visible_through_index_and_show_without_financial_side_effects(): void
    {
        [$actor, $organization] = $this->context('S068 API master');
        $this->grant($actor, $organization, 'compensation.manage');
        Sanctum::actingAs($actor);

        $response = $this->organizationRequest($organization)->post('/api/v1/bank-statement-imports', $this->payload(
            UploadedFile::fake()->createWithContent('statement.csv', $this->csv()),
            (string) Str::uuid(),
        ));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.source_row_count', 1)
            ->assertJsonPath('data.accepted_row_count', 1)
            ->assertJsonPath('data.duplicate_candidate_row_count', 0)
            ->assertJsonPath('data.rejected_row_count', 0);

        $batch = BankStatementImportBatch::query()->sole();
        $this->organizationRequest($organization)->getJson('/api/v1/bank-statement-imports')
            ->assertOk()
            ->assertJsonPath('data.items.0.public_id', $batch->public_id);
        $this->organizationRequest($organization)->getJson('/api/v1/bank-statement-imports/'.$batch->public_id)
            ->assertOk()
            ->assertJsonPath('data.public_id', $batch->public_id)
            ->assertJsonPath('data.rows.0.status', 'accepted');

        self::assertDatabaseCount('bank_transaction_evidence', 1);
        self::assertDatabaseCount('bank_transaction_evidence_events', 1);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }

    public function test_import_requires_permission_and_batch_is_hidden_across_organization_contexts(): void
    {
        [$actor, $first] = $this->context('S068 first organization');
        $second = Organization::query()->create(['name' => 'S068 second organization', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        OrganizationMembership::query()->create([
            'organization_id' => $second->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2026-01-01',
        ]);
        Sanctum::actingAs($actor);

        $this->organizationRequest($first)->post('/api/v1/bank-statement-imports', $this->payload(
            UploadedFile::fake()->createWithContent('forbidden.csv', $this->csv()),
            (string) Str::uuid(),
        ))->assertForbidden();
        self::assertDatabaseCount('bank_statement_import_batches', 0);

        $this->grant($actor, $first, 'compensation.manage');
        $created = $this->organizationRequest($first)->post('/api/v1/bank-statement-imports', $this->payload(
            UploadedFile::fake()->createWithContent('first.csv', $this->csv()),
            (string) Str::uuid(),
        ))->assertCreated();
        $batchPublicId = (string) $created->json('data.public_id');

        $this->grant($actor, $second, 'compensation.manage');
        $this->organizationRequest($second)->getJson('/api/v1/bank-statement-imports/'.$batchPublicId)->assertNotFound();
        $this->organizationRequest($second)->getJson('/api/v1/bank-statement-imports')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_invalid_mapping_and_non_csv_file_are_rejected_without_import_rows(): void
    {
        [$actor, $organization] = $this->context('S068 validation organization');
        $this->grant($actor, $organization, 'compensation.manage');
        Sanctum::actingAs($actor);

        $invalid = $this->payload(UploadedFile::fake()->createWithContent('statement.csv', $this->csv()), (string) Str::uuid());
        unset($invalid['mapping']['amount']);
        $this->organizationRequest($organization)->post('/api/v1/bank-statement-imports', $invalid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mapping.amount');

        $wrongFile = $this->payload(UploadedFile::fake()->createWithContent('statement.exe', 'not csv'), (string) Str::uuid());
        $this->organizationRequest($organization)->post('/api/v1/bank-statement-imports', $wrongFile)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        self::assertDatabaseCount('bank_statement_import_batches', 0);
        self::assertDatabaseCount('bank_statement_import_rows', 0);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
    }

    private function context(string $name): array
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2026-01-01',
        ]);

        return [$actor, $organization];
    }

    private function grant(User $actor, Organization $organization, string $permission): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId((int) $organization->id);
            $registrar->forgetCachedPermissions();
            $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
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

    private function payload(UploadedFile $file, string $idempotencyKey): array
    {
        return [
            'idempotency_key' => $idempotencyKey, 'file' => $file, 'delimiter' => ';', 'encoding' => 'UTF-8',
            'mapping_version' => 's068-api-v1', 'date_format' => 'Y-m-d', 'decimal_separator' => ',',
            'mapping' => [
                'booked_at' => 'Booked', 'value_date' => 'Value', 'amount' => 'Amount', 'currency' => 'Currency',
                'source_reference' => 'Reference', 'bank_statement_reference' => 'Statement', 'account_identifier' => 'Account',
                'counterparty_name' => 'Counterparty', 'counterparty_account_identifier' => 'CounterpartyAccount',
                'variable_symbol' => 'VS', 'message' => 'Message',
            ],
        ];
    }

    private function csv(): string
    {
        return "Booked;Value;Amount;Currency;Account;Counterparty;CounterpartyAccount;VS;Message;Reference;Statement\n2026-09-07;2026-09-08;1250,50;CZK;CZ-MASTER;Customer One;CZ-COUNTERPARTY;68001;Invoice 68001;BANK-68001;STATEMENT-09\n";
    }
}
