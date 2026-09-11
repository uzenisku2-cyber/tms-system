<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementStatementLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_statement_is_reviewed_approved_closed_and_cancelled_idempotently_without_financial_execution(): void
    {
        $owner = Organization::query()->create(['name' => 'Master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
        $recipient = Organization::query()->create(['name' => 'Carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE]);
        $actor = User::factory()->create();
        OrganizationMembership::query()->create(['organization_id' => $owner->id, 'user_id' => $actor->id, 'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER, 'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay()]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $owner->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $owner->id);

        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'recipient_type' => 'organization', 'recipient_organization_id' => $recipient->id,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'status' => 'draft', 'earning_amount_minor' => 100000, 'deduction_amount_minor' => 25000,
            'net_balance_minor' => 75000, 'source_snapshot' => ['line_count' => 2],
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => str_repeat('a', 64),
            'revision' => 1, 'created_by_user_id' => $actor->id,
        ]);
        $url = "/api/v1/financial-settlement-statements/{$statement->public_id}/transitions";
        $revision = 1;
        foreach (['submit' => 'under_review', 'approve' => 'approved', 'close' => 'closed', 'cancel' => 'cancelled'] as $action => $status) {
            $payload = ['action' => $action, 'idempotency_key' => (string) Str::uuid(), 'expected_revision' => $revision, 'reason' => "Lifecycle {$action}."];
            $response = $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.status', $status)->assertJsonPath('data.revision', $revision + 1);
            $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.public_id', $response->json('data.public_id'));
            $revision++;
        }
        self::assertDatabaseCount('financial_settlement_statement_events', 4);
        self::assertDatabaseCount('billing_documents', 0);
        self::assertDatabaseCount('bank_transaction_evidence', 0);
    }
}
