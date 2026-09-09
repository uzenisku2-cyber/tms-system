<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankStatementImportBatch;
use App\Modules\Fleet\Models\BankStatementImportDuplicateCandidate;
use App\Modules\Fleet\Models\BankStatementImportDuplicateResolution;
use App\Modules\Fleet\Models\BankStatementImportRow;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BankStatementImportDuplicateResolutionApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_resolution_is_authorized_scoped_idempotent_audited_and_non_matching(): void
    {
        $actor = User::factory()->create();
        $first = $this->organization('S071 first', $actor);
        $second = $this->organization('S071 second', $actor);
        $candidate = $this->candidate($first, $actor);
        Sanctum::actingAs($actor);
        $url = '/api/v1/bank-statement-import-duplicate-candidates/'.$candidate->public_id.'/resolution';
        $payload = ['idempotency_key' => (string) Str::uuid(), 'decision' => 'confirmed_duplicate', 'reason' => 'The imported row represents the existing bank evidence.'];

        $this->withHeader('X-Organization-ID', (string) $first->id)->postJson($url, $payload)->assertForbidden();
        $this->grant($actor, $first);
        $created = $this->withHeader('X-Organization-ID', (string) $first->id)->postJson($url, $payload);
        $created->assertCreated()->assertJsonPath('data.decision', 'confirmed_duplicate')->assertJsonPath('data.reason', $payload['reason']);
        $publicId = $created->json('data.public_id');
        $this->withHeader('X-Organization-ID', (string) $first->id)->postJson($url, $payload)->assertCreated()->assertJsonPath('data.public_id', $publicId);

        $this->grant($actor, $second);
        $this->withHeader('X-Organization-ID', (string) $second->id)->postJson($url, ['idempotency_key' => (string) Str::uuid(), 'decision' => 'dismissed', 'reason' => 'Wrong organization.'])->assertNotFound();

        self::assertDatabaseCount('bank_statement_import_duplicate_resolutions', 1);
        $resolution = BankStatementImportDuplicateResolution::query()->sole();
        self::assertSame((int) $first->id, (int) $resolution->organization_context_id);
        self::assertSame((int) $actor->id, (int) $resolution->resolved_by_user_id);
        self::assertSame('unresolved', $candidate->fresh()->decision);
        self::assertDatabaseCount('bank_transaction_evidence', 1);
        self::assertDatabaseCount('bank_transaction_evidence_events', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }

    private function organization(string $name, User $actor): Organization
    {
        $organization = Organization::query()->create(['name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        OrganizationMembership::query()->create(['organization_id' => $organization->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2026-01-01']);

        return $organization;
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

    private function candidate(Organization $organization, User $actor): BankStatementImportDuplicateCandidate
    {
        $evidence = BankTransactionEvidence::query()->create(['public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id, 'idempotency_key' => (string) Str::uuid(), 'source_type' => 'bank_import', 'source_reference' => 's071-existing', 'direction' => 'credit', 'booked_at' => '2026-09-08', 'amount' => '100.00', 'currency' => 'CZK', 'evidence_note' => 'Existing bank evidence for duplicate resolution lifecycle test.', 'status' => 'recorded', 'recorded_by_user_id' => $actor->id, 'recorded_at' => now(), 'revision' => 1]);
        $batch = BankStatementImportBatch::query()->create(['public_id' => (string) Str::uuid(), 'organization_context_id' => $organization->id, 'idempotency_key' => (string) Str::uuid(), 'status' => 'completed_with_review', 'original_filename' => 's071.csv', 'file_sha256' => hash('sha256', 's071'), 'source_type' => 'csob_csv', 'parser_version' => 'csob-csv-v1', 'mapping_version' => 'csob-csv-v1', 'delimiter' => ';', 'encoding' => 'UTF-8', 'mapping' => [], 'source_row_count' => 1, 'accepted_row_count' => 0, 'duplicate_candidate_row_count' => 1, 'rejected_row_count' => 0, 'imported_by_user_id' => $actor->id, 'completed_at' => now()]);
        $row = BankStatementImportRow::query()->create(['bank_statement_import_batch_id' => $batch->id, 'source_row' => 4, 'status' => 'duplicate_candidate', 'row_fingerprint' => hash('sha256', 'row'), 'transaction_fingerprint' => hash('sha256', 'transaction'), 'raw_payload' => [], 'normalized_payload' => [], 'validation_messages' => []]);

        return BankStatementImportDuplicateCandidate::query()->create(['public_id' => (string) Str::uuid(), 'bank_statement_import_row_id' => $row->id, 'candidate_bank_transaction_evidence_id' => $evidence->id, 'comparison_method' => 'probable_core_fields', 'matching_fields' => ['booked_at', 'amount'], 'confidence' => 0.8, 'decision' => 'unresolved', 'detected_at' => now()]);
    }
}
