<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Fleet\Services\BankTransactionEvidenceService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\FinancialSettlementBankMatchCandidate;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Services\FinancialSettlementBankMatchCandidateProposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialSettlementBankMatchCandidateProposalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_proposal_is_partial_directional_explainable_idempotent_and_non_executing(): void
    {
        $organization = Organization::query()->create([
            'name' => 'S079 master', 'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE,
        ]);
        $carrier = Organization::query()->create([
            'name' => 'S079 carrier', 'type' => Organization::TYPE_CARRIER, 'status' => Organization::STATUS_ACTIVE,
        ]);
        $actor = User::factory()->create();
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');

        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'counterparty_organization_id' => $carrier->id, 'driver_id' => null,
            'document_type' => BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => '1250.00', 'vat_rate' => null, 'vat_amount' => '0.00', 'gross_amount' => '1250.00',
            'status' => 'draft', 'source_snapshot' => [], 'created_by_user_id' => $actor->id,
        ]);
        BillingDocumentCommercialIdentity::query()->create([
            'public_id' => (string) Str::uuid(), 'billing_document_id' => $document->id,
            'owner_organization_id' => $organization->id, 'direction' => BillingDocumentCommercialIdentity::DIRECTION_PAYABLE,
            'document_number' => 'SET-079-001', 'variable_symbol' => '79001',
            'issued_on' => '2026-09-13', 'taxable_supply_on' => null, 'due_on' => '2026-09-20',
            'counterparty_name' => 'S079 Carrier s.r.o.', 'counterparty_registration_number' => null,
            'counterparty_vat_number' => null, 'counterparty_account_identifier' => '123456789/0100',
            'counterparty_snapshot' => [], 'revision' => 1, 'idempotency_key' => (string) Str::uuid(),
            'command_fingerprint' => hash('sha256', 's079-commercial-identity'), 'created_by_user_id' => $actor->id,
        ]);
        $statement = FinancialSettlementStatement::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $organization->id,
            'recipient_type' => FinancialSettlementStatement::RECIPIENT_ORGANIZATION,
            'recipient_organization_id' => $carrier->id, 'recipient_driver_id' => null,
            'period_from' => '2026-08-01', 'period_until' => '2026-08-31', 'currency' => 'CZK',
            'status' => FinancialSettlementStatement::STATUS_CLOSED, 'earning_amount_minor' => 150000,
            'deduction_amount_minor' => 25000, 'net_balance_minor' => 125000, 'source_snapshot' => [],
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => hash('sha256', 's079-statement'),
            'revision' => 5, 'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
            'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $document->id,
            'output_kind' => FinancialSettlementStatement::OUTPUT_CARRIER_PAYABLE,
            'output_direction' => BillingDocumentCommercialIdentity::DIRECTION_PAYABLE, 'output_materialized_at' => now(),
        ]);
        app(BankTransactionEvidenceService::class)->record([
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S079-WRONG-AMOUNT', 'bank_statement_reference' => 'S079-STATEMENT',
            'direction' => 'debit', 'booked_at' => '2026-09-18', 'value_date' => '2026-09-18',
            'amount' => '1249.99', 'currency' => 'EUR', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'S079 Carrier s.r.o.', 'counterparty_account_identifier' => '123456789/0100',
            'variable_symbol' => '79001', 'message' => 'Wrong amount', 'evidence_note' => 'Must be ignored.',
        ], (int) $organization->id, $actor);
        app(BankTransactionEvidenceService::class)->record([
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-S079-EXACT', 'bank_statement_reference' => 'S079-STATEMENT',
            'direction' => 'debit', 'booked_at' => '2026-09-18', 'value_date' => '2026-09-18',
            'amount' => '750.00', 'currency' => 'CZK', 'account_identifier' => 'CZ-MASTER',
            'counterparty_name' => 'S079 Carrier s.r.o.', 'counterparty_account_identifier' => '123456789/0100',
            'variable_symbol' => '79001', 'message' => 'Partial settlement output', 'evidence_note' => 'S081 proposal evidence.',
        ], (int) $organization->id, $actor);

        $payload = [
            'idempotency_key' => '0da985d3-60bb-4405-945f-ff07fcfdf274',
            'minimum_score_basis_points' => 6000, 'date_window_days' => 7,
            'reason' => 'Propose a partial carrier settlement bank match candidate.',
        ];
        $created = app(FinancialSettlementBankMatchCandidateProposalService::class)
            ->propose((string) $statement->public_id, $payload, (int) $organization->id, $actor);
        self::assertFalse($created['replayed']);
        self::assertSame('debit', $created['data']['expected_bank_direction']);
        self::assertSame(75000, $created['data']['bank_amount_minor']);
        self::assertSame(75000, $created['data']['proposed_amount_minor']);
        self::assertSame(8500, $created['data']['score_basis_points']);
        self::assertFalse($created['data']['match_reasons']['amount_exact']);
        self::assertTrue($created['data']['match_reasons']['amount_partial']);
        self::assertFalse($created['data']['payment_allocation_created']);
        self::assertFalse($created['data']['payment_marked']);
        self::assertFalse($created['data']['bank_matching_performed']);

        $replayed = app(FinancialSettlementBankMatchCandidateProposalService::class)
            ->propose((string) $statement->public_id, $payload, (int) $organization->id, $actor);
        self::assertTrue($replayed['replayed']);
        self::assertSame($created['data']['public_id'], $replayed['data']['public_id']);
        self::assertDatabaseCount('financial_settlement_bank_match_candidates', 1);
        self::assertDatabaseCount('financial_settlement_bank_match_candidate_events', 1);
        self::assertSame(FinancialSettlementBankMatchCandidate::STATUS_PROPOSED, FinancialSettlementBankMatchCandidate::query()->sole()->status);
        self::assertSame(5, (int) $statement->fresh()->revision);
        self::assertSame('draft', $document->fresh()->status);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
        self::assertDatabaseCount('vehicle_cost_allocation_bank_matching_executions', 0);
    }
}
