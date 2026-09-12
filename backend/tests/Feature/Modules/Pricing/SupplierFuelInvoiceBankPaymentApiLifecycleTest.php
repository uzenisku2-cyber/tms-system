<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\SupplierFuelInvoiceBankPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceBankPaymentApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_payment_is_exact_partial_idempotent_scoped_reversible_and_non_settling(): void
    {
        $organization = $this->organization('S076 master');
        $actor = User::factory()->create();
        $this->authorizeActor($actor, $organization);
        $this->authenticate($actor, $organization);
        $invoice = $this->invoice('ORLEN-PAY-001', '1210.00');
        $firstEvidence = $this->evidence($organization, $actor, 'BANK-S076-001', '700.00', 'debit');
        $secondEvidence = $this->evidence($organization, $actor, 'BANK-S076-002', '510.00', 'debit');
        $url = "/api/v1/supplier-fuel-invoices/{$invoice}/bank-payments";
        $firstPayload = [
            'idempotency_key' => '1a63fdd8-e027-4a71-bc48-5c4933c76101',
            'bank_transaction_evidence_public_id' => $firstEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '700.00', 'reason' => 'First partial payment.',
        ];

        $first = $this->postJson($url, $firstPayload)->assertCreated()
            ->assertJsonPath('data.invoice_payment_state', 'partially_paid')
            ->assertJsonPath('data.invoice_paid_amount_minor', 70000)
            ->assertJsonPath('data.invoice_unpaid_amount_minor', 51000)
            ->assertJsonPath('data.bank_transaction_unallocated_amount_minor', 0)
            ->assertJsonPath('data.bank_matching_performed', true)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.fuel_settlement_mutated', false);
        $this->postJson($url, $firstPayload)->assertOk()
            ->assertJsonPath('data.public_id', $first->json('data.public_id'));

        $changed = $firstPayload;
        $changed['allocated_amount'] = '699.00';
        $this->postJson($url, $changed)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');

        $secondPayload = [
            'idempotency_key' => 'c765d171-fc30-4ad9-8911-d27c3c9cf9dc',
            'bank_transaction_evidence_public_id' => $secondEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '510.00', 'reason' => 'Final payment.',
        ];
        $second = $this->postJson($url, $secondPayload)->assertCreated()
            ->assertJsonPath('data.invoice_payment_state', 'paid')
            ->assertJsonPath('data.invoice_unpaid_amount_minor', 0)
            ->assertJsonPath('data.payment_marked', true);

        $thirdEvidence = $this->evidence($organization, $actor, 'BANK-S076-003', '1.00', 'debit');
        $this->postJson($url, [
            'idempotency_key' => (string) Str::uuid(),
            'bank_transaction_evidence_public_id' => $thirdEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '1.00', 'reason' => 'Invoice overflow.',
        ])->assertUnprocessable()->assertJsonValidationErrors('allocated_amount');

        $otherInvoice = $this->invoice('ORLEN-PAY-002', '12.10');
        $this->postJson("/api/v1/supplier-fuel-invoices/{$otherInvoice}/bank-payments", [
            'idempotency_key' => (string) Str::uuid(),
            'bank_transaction_evidence_public_id' => $firstEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '1.00', 'reason' => 'Bank evidence overflow.',
        ])->assertUnprocessable()->assertJsonValidationErrors('allocated_amount');

        $creditEvidence = $this->evidence($organization, $actor, 'BANK-S076-CREDIT', '10.00', 'credit');
        $this->postJson("/api/v1/supplier-fuel-invoices/{$otherInvoice}/bank-payments", [
            'idempotency_key' => (string) Str::uuid(),
            'bank_transaction_evidence_public_id' => $creditEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '10.00', 'reason' => 'Credit is not a supplier payment.',
        ])->assertUnprocessable()->assertJsonValidationErrors('bank_transaction_evidence_public_id');

        $payment = SupplierFuelInvoiceBankPayment::query()->where('public_id', $second->json('data.public_id'))->sole();
        $reverseUrl = $url.'/'.$payment->public_id.'/reverse';
        $reversePayload = [
            'idempotency_key' => '8190e9b2-f988-4404-8f3e-b9e7feb17ca0',
            'expected_revision' => 1, 'reason' => 'Payment assigned incorrectly.',
        ];
        $this->postJson($reverseUrl, $reversePayload)->assertOk()
            ->assertJsonPath('data.status', 'reversed')
            ->assertJsonPath('data.revision', 2)
            ->assertJsonPath('data.invoice_payment_state', 'partially_paid')
            ->assertJsonPath('data.invoice_unpaid_amount_minor', 51000)
            ->assertJsonPath('data.bank_transaction_unallocated_amount_minor', 51000)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonCount(2, 'data.events');
        $this->postJson($reverseUrl, $reversePayload)->assertOk()->assertJsonPath('data.status', 'reversed');

        $foreign = $this->organization('S076 foreign');
        $foreignActor = User::factory()->create();
        $this->authorizeActor($foreignActor, $foreign);
        $this->authenticate($foreignActor, $foreign);
        $this->postJson($url, [
            'idempotency_key' => (string) Str::uuid(),
            'bank_transaction_evidence_public_id' => $thirdEvidence,
            'expected_bank_transaction_evidence_revision' => 1,
            'allocated_amount' => '1.00', 'reason' => 'Cross organization.',
        ])->assertNotFound();

        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 2);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payment_events', 3);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
        self::assertDatabaseCount('fuel_transaction_settlement_applications', 0);
        self::assertDatabaseCount('financial_settlement_statements', 0);
    }

    private function invoice(string $number, string $gross): string
    {
        $minor = (int) str_replace('.', '', $gross);
        $netMinor = (int) round($minor / 1.21);
        $vatMinor = $minor - $netMinor;
        $money = static fn (int $value): string => intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
        $response = $this->postJson('/api/v1/supplier-fuel-invoices', [
            'idempotency_key' => (string) Str::uuid(), 'document_number' => $number,
            'issued_on' => '2026-09-11', 'taxable_supply_on' => '2026-08-31', 'due_on' => '2026-09-25',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.', 'currency' => 'CZK',
            'description' => 'S076 supplier fuel invoice', 'net_amount' => $money($netMinor),
            'vat_rate_basis_points' => 2100, 'vat_amount' => $money($vatMinor), 'gross_amount' => $gross,
        ])->assertCreated();

        return (string) $response->json('data.public_id');
    }

    private function evidence(Organization $organization, User $actor, string $reference, string $amount, string $direction): string
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->unsetRelation('permissions');

        $result = app(BankTransactionEvidenceService::class)->record([
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => $reference, 'bank_statement_reference' => 'S076-STATEMENT',
            'direction' => $direction, 'booked_at' => '2026-09-11', 'value_date' => '2026-09-11',
            'amount' => $amount, 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'ORLEN Unipetrol RPA s.r.o.',
            'counterparty_account_identifier' => '123456789/0100', 'variable_symbol' => '76001',
            'message' => 'Supplier fuel invoice payment', 'evidence_note' => 'S076 payment evidence.',
        ], (int) $organization->id, $actor);

        return (string) $result['bank_transaction_evidence_public_id'];
    }

    private function authenticate(User $user, Organization $organization): void
    {
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $organization->id);
    }

    private function authorizeActor(User $user, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $user->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $user->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create([
            'name' => $name, 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
