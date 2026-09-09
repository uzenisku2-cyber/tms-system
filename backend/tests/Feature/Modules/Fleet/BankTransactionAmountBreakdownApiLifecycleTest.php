<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Fleet;

use App\Models\User;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BankTransactionAmountBreakdownApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_breakdown_is_scoped_exact_versioned_idempotent_audited_and_non_executing(): void
    {
        $actor = User::factory()->create();
        $first = $this->organization('S072 first', $actor);
        $second = $this->organization('S072 second', $actor);
        $evidence = $this->evidence($first, $actor, '100.00', 's072-primary');
        $url = '/api/v1/bank-transaction-evidence/'.$evidence->public_id.'/amount-breakdowns';
        $idempotencyKey = (string) Str::uuid();
        $draft = [
            'idempotency_key' => $idempotencyKey,
            'expected_revision' => 0,
            'finalize' => false,
            'reason' => 'Initial manual allocation of the received bank amount.',
            'components' => [
                ['type' => 'vat', 'label' => 'VAT', 'amount' => '21.00'],
                ['type' => 'deductible', 'label' => 'Deductible', 'amount' => '80.00'],
                ['type' => 'custom', 'label' => 'Rounding correction', 'amount' => '-1.00'],
            ],
        ];

        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $first->id)->postJson($url, $draft)->assertForbidden();

        $this->grant($actor, $first);
        $created = $this->withHeader('X-Organization-ID', (string) $first->id)->postJson($url, $draft);
        $created->assertCreated()
            ->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.source_amount_minor', 10000)
            ->assertJsonPath('data.allocated_amount_minor', 10000)
            ->assertJsonCount(3, 'data.components')
            ->assertJsonPath('data.components.2.amount_minor', -100)
            ->assertJsonPath('data.automatic_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.billing_document_mutated', false);

        $publicId = $created->json('data.public_id');
        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($url, $draft)
            ->assertCreated()
            ->assertJsonPath('data.public_id', $publicId);

        $idempotencyConflict = $draft;
        $idempotencyConflict['reason'] = 'A different command must not reuse the same idempotency key.';
        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($url, $idempotencyConflict)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');

        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.latest_revision', 1)
            ->assertJsonCount(1, 'data.revisions');

        $stale = $draft;
        $stale['idempotency_key'] = (string) Str::uuid();
        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($url, $stale)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_revision');

        $finalized = [
            'idempotency_key' => (string) Str::uuid(),
            'expected_revision' => 1,
            'finalize' => true,
            'reason' => 'Reviewed component allocation finalized to the exact bank amount.',
            'components' => [
                ['type' => 'vat', 'label' => 'VAT', 'amount' => '21.00'],
                ['type' => 'deductible', 'label' => 'Deductible', 'amount' => '79.00'],
                ['type' => 'custom', 'label' => 'Manual correction', 'amount' => '0.00'],
            ],
        ];

        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($url, $finalized)
            ->assertCreated()
            ->assertJsonPath('data.revision', 2)
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.allocated_amount_minor', 10000);

        $afterFinalization = $finalized;
        $afterFinalization['idempotency_key'] = (string) Str::uuid();
        $afterFinalization['expected_revision'] = 2;
        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($url, $afterFinalization)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('breakdown');

        $this->grant($actor, $second);
        $this->withHeader('X-Organization-ID', (string) $second->id)->getJson($url)->assertNotFound();

        $unbalancedEvidence = $this->evidence($first, $actor, '100.00', 's072-unbalanced');
        $unbalancedUrl = '/api/v1/bank-transaction-evidence/'.$unbalancedEvidence->public_id.'/amount-breakdowns';
        $unbalanced = [
            'idempotency_key' => (string) Str::uuid(),
            'expected_revision' => 0,
            'finalize' => false,
            'reason' => 'This deliberately differs by one cent.',
            'components' => [
                ['type' => 'custom', 'label' => 'Incorrect total', 'amount' => '99.99'],
            ],
        ];

        $this->withHeader('X-Organization-ID', (string) $first->id)
            ->postJson($unbalancedUrl, $unbalanced)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('components');

        self::assertDatabaseCount('bank_transaction_amount_breakdowns', 2);
        self::assertDatabaseCount('bank_transaction_amount_breakdown_components', 6);
        self::assertDatabaseCount('bank_transaction_amount_breakdown_events', 2);
        self::assertDatabaseCount('bank_transaction_evidence', 2);
        self::assertDatabaseCount('bank_transaction_evidence_events', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('financial_calculations', 0);
    }

    private function organization(string $name, User $actor): Organization
    {
        $organization = Organization::query()->create([
            'name' => $name,
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

    private function evidence(Organization $organization, User $actor, string $amount, string $reference): BankTransactionEvidence
    {
        return BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_context_id' => $organization->id,
            'idempotency_key' => (string) Str::uuid(),
            'source_type' => 'bank_import',
            'source_reference' => $reference,
            'direction' => 'credit',
            'booked_at' => '2026-09-09',
            'amount' => $amount,
            'currency' => 'CZK',
            'evidence_note' => 'Bank evidence for amount breakdown lifecycle testing.',
            'status' => 'recorded',
            'recorded_by_user_id' => $actor->id,
            'recorded_at' => now(),
            'revision' => 1,
        ]);
    }
}
