<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fuel\Models\FuelCard;
use App\Modules\Fuel\Models\FuelCardSettlementPolicy;
use App\Modules\Fuel\Models\FuelImportBatch;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionSettlementApplication;
use App\Modules\Fuel\Models\FuelTransactionSettlementEligibility;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialSettlementStatement;
use App\Modules\Pricing\Models\FinancialSettlementStatementLine;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Models\SupplierFuelInvoiceTransactionAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SupplierFuelInvoiceRebillingCoverageApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_driver_and_carrier_coverage_is_exact_positive_idempotent_scoped_and_non_executing(): void
    {
        $owner = $this->organization('S076 owner', Organization::TYPE_MASTER);
        $carrier = $this->organization('S076 carrier', Organization::TYPE_CARRIER);
        $actor = $this->actor($owner);
        $driver = $this->driver('Positive');

        $driverCase = $this->coverageCase($owner, $actor, $driver, null, 'driver', 'net', 110000, true);
        $counts = $this->financialCounts();
        $payload = ['idempotency_key' => (string) Str::uuid(), 'comparison_basis' => 'net'];
        $url = "/api/v1/supplier-fuel-invoices/{$driverCase['invoice']}/rebilling-coverages";
        $created = $this->postJson($url, $payload);
        $created->assertCreated()
            ->assertJsonPath('data.status', 'complete')
            ->assertJsonPath('data.purchase_amount_minor', 100000)
            ->assertJsonPath('data.rebilled_amount_minor', 110000)
            ->assertJsonPath('data.unrebilled_amount_minor', 0)
            ->assertJsonPath('data.margin_minor', 10000)
            ->assertJsonPath('data.lines.0.recipient_type', 'driver')
            ->assertJsonPath('data.positive_margin_allowed', true)
            ->assertJsonPath('data.bank_matching_performed', false)
            ->assertJsonPath('data.payment_marked', false)
            ->assertJsonPath('data.settlement_mutated', false);
        $this->postJson($url, $payload)->assertOk()
            ->assertJsonPath('data.public_id', $created->json('data.public_id'));

        $carrierCase = $this->coverageCase($owner, $actor, $driver, $carrier, 'carrier', 'gross', 130000, true);
        $carrierResponse = $this->postJson(
            "/api/v1/supplier-fuel-invoices/{$carrierCase['invoice']}/rebilling-coverages",
            ['idempotency_key' => (string) Str::uuid(), 'comparison_basis' => 'gross'],
        );
        $carrierResponse->assertCreated()
            ->assertJsonPath('data.status', 'complete')
            ->assertJsonPath('data.purchase_amount_minor', 121000)
            ->assertJsonPath('data.rebilled_amount_minor', 130000)
            ->assertJsonPath('data.margin_minor', 9000)
            ->assertJsonPath('data.lines.0.recipient_type', 'carrier');

        $this->assertFinancialCountsUnchangedExceptCoverage($counts, 2, 1, 1);

        $other = $this->organization('S076 other', Organization::TYPE_MASTER);
        $otherActor = $this->actor($other);
        Sanctum::actingAs($otherActor);
        $this->withHeader('X-Organization-ID', (string) $other->id);
        $this->postJson($url, ['idempotency_key' => (string) Str::uuid(), 'comparison_basis' => 'net'])->assertNotFound();
    }

    public function test_negative_margin_and_missing_chain_remain_review_findings(): void
    {
        $owner = $this->organization('S076 review owner', Organization::TYPE_MASTER);
        $actor = $this->actor($owner);
        $driver = $this->driver('Review');

        $negative = $this->coverageCase($owner, $actor, $driver, null, 'driver', 'net', 90000, true);
        $response = $this->postJson(
            "/api/v1/supplier-fuel-invoices/{$negative['invoice']}/rebilling-coverages",
            ['idempotency_key' => (string) Str::uuid(), 'comparison_basis' => 'net'],
        );
        $response->assertCreated()
            ->assertJsonPath('data.status', 'negative_margin')
            ->assertJsonPath('data.rebilled_amount_minor', 90000)
            ->assertJsonPath('data.unrebilled_amount_minor', 10000)
            ->assertJsonPath('data.margin_minor', -10000)
            ->assertJsonPath('data.lines.0.status', 'negative_margin');

        $missing = $this->coverageCase($owner, $actor, $driver, null, 'driver', 'net', null, false);
        $missingResponse = $this->postJson(
            "/api/v1/supplier-fuel-invoices/{$missing['invoice']}/rebilling-coverages",
            ['idempotency_key' => (string) Str::uuid(), 'comparison_basis' => 'net'],
        );
        $missingResponse->assertCreated()
            ->assertJsonPath('data.status', 'incomplete')
            ->assertJsonPath('data.rebilled_amount_minor', 0)
            ->assertJsonPath('data.unrebilled_amount_minor', 100000)
            ->assertJsonPath('data.lines.0.status', 'missing');
    }

    /** @return array{invoice: string} */
    private function coverageCase(
        Organization $owner,
        User $actor,
        Driver $driver,
        ?Organization $carrier,
        string $target,
        string $basis,
        ?int $appliedMinor,
        bool $materialized,
    ): array {
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $owner->id);
        $document = BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'document_type' => BillingDocument::TYPE_SUPPLIER_FUEL_INVOICE,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_STANDARD, 'vat_status_snapshot' => 'payer',
            'net_amount' => '1000.00', 'vat_rate' => '21.00', 'vat_amount' => '210.00',
            'gross_amount' => '1210.00', 'status' => 'draft', 'source_snapshot' => [],
            'created_by_user_id' => $actor->id,
        ]);
        $identity = BillingDocumentCommercialIdentity::query()->create([
            'public_id' => (string) Str::uuid(), 'billing_document_id' => $document->id,
            'owner_organization_id' => $owner->id, 'direction' => 'payable',
            'document_number' => 'S076-'.Str::upper(Str::random(12)), 'issued_on' => '2026-09-01',
            'due_on' => '2026-09-30', 'counterparty_name' => 'Fuel supplier',
            'counterparty_snapshot' => ['name' => 'Fuel supplier'], 'revision' => 1,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => str_repeat('a', 64),
            'created_by_user_id' => $actor->id,
        ]);
        $batch = FuelImportBatch::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'provider' => 'ORLEN', 'status' => 'completed', 'original_filename' => Str::uuid().'.csv',
            'file_sha256' => hash('sha256', (string) Str::uuid()), 'schema_fingerprint' => str_repeat('b', 64),
            'source_row_count' => 1, 'accepted_row_count' => 1, 'imported_by_user_id' => $actor->id,
            'completed_at' => now(),
        ]);
        $cardCode = 'S076-'.Str::upper(Str::random(10));
        $transaction = FuelTransaction::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'provider' => 'ORLEN', 'provider_transaction_identifier' => (string) Str::uuid(),
            'transaction_fingerprint' => hash('sha256', (string) Str::uuid()),
            'occurred_at' => '2026-09-10 10:00:00', 'provider_card_identifier' => $cardCode,
            'responsible_organization_id' => $carrier instanceof Organization ? $carrier->id : $owner->id, 'driver_id' => $driver->id,
            'match_status' => 'matched', 'match_method' => 'provider_card_and_assignment_period',
            'quantity' => '40.000000', 'unit_of_measure' => 'L', 'net_amount' => '1000.000000',
            'tax_amount' => '210.000000', 'gross_amount' => '1210.000000', 'currency' => 'CZK',
            'fuel_import_batch_id' => $batch->id, 'source_row' => 2,
        ]);
        SupplierFuelInvoiceTransactionAllocation::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'billing_document_id' => $document->id, 'fuel_transaction_id' => $transaction->id,
            'idempotency_key' => (string) Str::uuid(), 'command_fingerprint' => str_repeat('c', 64),
            'allocated_amount_minor' => 121000, 'currency' => 'CZK', 'status' => 'active',
            'revision' => 1, 'reason' => 'Coverage test.', 'created_by_user_id' => $actor->id,
        ]);

        if ($appliedMinor !== null) {
            $card = FuelCard::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
                'provider' => 'ORLEN', 'provider_card_identifier' => $cardCode,
                'masked_card_number' => '**** 0076', 'status' => 'active', 'valid_from' => '2026-01-01',
                'currency' => 'CZK', 'lock_version' => 1, 'created_by_user_id' => $actor->id,
            ]);
            $policy = FuelCardSettlementPolicy::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
                'fuel_card_id' => $card->id, 'settlement_target' => $target,
                'discount_beneficiary' => $target, 'amount_basis' => $basis,
                'vat_mode' => $target === 'driver' ? 'not_applicable' : 'counterparty_tax_profile',
                'valid_from' => '2026-01-01', 'reason' => 'Coverage test.', 'created_by_user_id' => $actor->id,
            ]);
            $eligibility = FuelTransactionSettlementEligibility::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
                'fuel_transaction_id' => $transaction->id, 'status' => 'eligible', 'result_code' => 'eligible',
                'fuel_card_settlement_policy_id' => $policy->id, 'reconciliation_revision' => 1,
                'settlement_target' => $target, 'target_organization_id' => $carrier?->id,
                'target_driver_id' => $target === 'driver' ? $driver->id : null,
                'discount_beneficiary' => $target, 'amount_basis' => $basis,
                'vat_mode' => $target === 'driver' ? 'not_applicable' : 'counterparty_tax_profile',
                'base_amount' => number_format($appliedMinor / 100, 6, '.', ''), 'currency' => 'CZK',
                'revision' => 1, 'evaluated_at' => now(),
            ]);
            $calculation = $this->calculation($owner, $actor, $driver);
            $application = FuelTransactionSettlementApplication::query()->create([
                'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
                'fuel_transaction_id' => $transaction->id,
                'fuel_transaction_settlement_eligibility_id' => $eligibility->id,
                'eligibility_revision' => 1, 'reconciliation_revision' => 1,
                'fuel_card_settlement_policy_id' => $policy->id, 'settlement_target' => $target,
                'target_organization_id' => $carrier?->id,
                'target_driver_id' => $target === 'driver' ? $driver->id : null,
                'discount_beneficiary' => $target, 'amount_basis' => $basis,
                'vat_mode' => $target === 'driver' ? 'not_applicable' : 'counterparty_tax_profile',
                'applied_amount' => number_format($appliedMinor / 100, 6, '.', ''),
                'currency' => 'CZK', 'financial_calculation_id' => $calculation->id,
                'status' => 'applied', 'revision' => 2, 'applied_by_user_id' => $actor->id,
                'applied_at' => now(),
            ]);
            if ($materialized) {
                $output = $this->outputDocument($owner, $carrier, $driver, $actor, $target, $appliedMinor);
                $statement = FinancialSettlementStatement::query()->create([
                    'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
                    'recipient_type' => $target === 'driver' ? 'driver' : 'organization',
                    'recipient_organization_id' => $carrier?->id,
                    'recipient_driver_id' => $target === 'driver' ? $driver->id : null,
                    'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
                    'status' => 'closed', 'earning_amount_minor' => 0,
                    'deduction_amount_minor' => $appliedMinor, 'net_balance_minor' => -$appliedMinor,
                    'source_snapshot' => [], 'idempotency_key' => (string) Str::uuid(),
                    'command_fingerprint' => str_repeat('d', 64), 'revision' => 4,
                    'created_by_user_id' => $actor->id, 'approved_by_user_id' => $actor->id,
                    'approved_at' => now(), 'closed_at' => now(), 'billing_document_id' => $output->id,
                    'output_kind' => $target === 'driver' ? 'driver_deduction' : 'carrier_receivable',
                    'output_direction' => $target === 'driver' ? 'internal' : 'receivable',
                    'output_materialized_at' => now(),
                ]);
                FinancialSettlementStatementLine::query()->create([
                    'public_id' => (string) Str::uuid(), 'financial_settlement_statement_id' => $statement->id,
                    'position' => 1, 'source_type' => 'financial_calculation',
                    'source_public_id' => (string) $calculation->public_id, 'source_revision' => 1,
                    'financial_calculation_id' => $calculation->id, 'effect' => 'deduction',
                    'description' => 'Fuel rebilling.', 'amount_minor' => $appliedMinor,
                    'currency' => 'CZK', 'source_snapshot' => ['application' => $application->public_id],
                    'created_at' => now(),
                ]);
            }
        }

        return ['invoice' => (string) $identity->public_id];
    }

    private function outputDocument(
        Organization $owner,
        ?Organization $carrier,
        Driver $driver,
        User $actor,
        string $target,
        int $amountMinor,
    ): BillingDocument {
        $amount = number_format($amountMinor / 100, 2, '.', '');

        return BillingDocument::query()->create([
            'public_id' => (string) Str::uuid(), 'owner_organization_id' => $owner->id,
            'counterparty_organization_id' => $carrier?->id,
            'driver_id' => $target === 'driver' ? $driver->id : null,
            'document_type' => $target === 'driver' ? BillingDocument::TYPE_DRIVER_REMUNERATION : BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30', 'currency' => 'CZK',
            'vat_treatment' => BillingDocument::VAT_NOT_APPLICABLE, 'vat_status_snapshot' => 'non_payer',
            'net_amount' => $amount, 'vat_rate' => null, 'vat_amount' => '0.00', 'gross_amount' => $amount,
            'status' => 'draft', 'source_snapshot' => ['coverage_test' => true], 'created_by_user_id' => $actor->id,
        ]);
    }

    private function calculation(Organization $provider, User $actor, Driver $driver): FinancialCalculation
    {
        $customer = $this->organization('S076 customer '.Str::uuid(), Organization::TYPE_CARRIER);
        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->id, 'target_organization_id' => $provider->id,
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE, 'valid_from' => '2026-01-01',
        ]);
        $route = 'S076-'.Str::upper(Str::random(12));
        $report = DailyReport::query()->create([
            'organization_id' => $customer->id, 'performed_by_driver_id' => $driver->id,
            'entered_by_user_id' => $actor->id, 'route_number' => $route,
            'route_number_normalized' => Str::lower($route), 'service_date' => '2026-09-10',
            'status' => DailyReport::STATUS_APPROVED, 'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false, 'delivered_parcels' => 1, 'redirected_parcels' => 0,
            'undelivered_parcels' => 0, 'planned_km' => '10.000', 'actual_km' => '10.000',
            'current_version' => 1, 'approved_at' => now(), 'approved_by_user_id' => $actor->id,
        ]);
        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->id, 'owner_organization_id' => $customer->id,
            'customer_organization_id' => $customer->id, 'provider_organization_id' => $provider->id,
            'name' => 'S076 pricing '.Str::uuid(), 'currency' => 'CZK', 'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1, 'created_by_user_id' => $actor->id,
        ]);
        $version = PriceListVersion::query()->create([
            'price_list_id' => $priceList->id, 'version_number' => 1, 'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-01-01', 'created_by_user_id' => $actor->id,
            'approved_by_user_id' => $actor->id, 'approved_at' => now(), 'activated_at' => now(),
        ]);

        return FinancialCalculation::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_id' => $provider->id,
            'organization_relationship_id' => $relationship->id, 'price_list_id' => $priceList->id,
            'price_list_version_id' => $version->id, 'daily_report_id' => $report->id,
            'daily_report_version' => 1, 'calculation_version' => 1,
            'status' => FinancialCalculation::STATUS_CALCULATED, 'currency' => 'CZK',
            'input_snapshot' => ['performed_by_driver_id' => $driver->id],
            'subtotal_amount' => '1000.00', 'total_amount' => '1000.00',
            'calculated_by_user_id' => $actor->id, 'calculated_at' => now(),
        ]);
    }

    /** @return array{billing_documents: int, settlement_applications: int, settlement_statements: int} */
    private function financialCounts(): array
    {
        return [
            'billing_documents' => BillingDocument::query()->count(),
            'settlement_applications' => FuelTransactionSettlementApplication::query()->count(),
            'settlement_statements' => FinancialSettlementStatement::query()->count(),
        ];
    }

    /** @param array{billing_documents: int, settlement_applications: int, settlement_statements: int} $before */
    private function assertFinancialCountsUnchangedExceptCoverage(array $before, int $documentsAdded, int $applicationsAdded, int $statementsAdded): void
    {
        self::assertSame($before['billing_documents'] + $documentsAdded, BillingDocument::query()->count());
        self::assertSame($before['settlement_applications'] + $applicationsAdded, FuelTransactionSettlementApplication::query()->count());
        self::assertSame($before['settlement_statements'] + $statementsAdded, FinancialSettlementStatement::query()->count());
        self::assertDatabaseCount('bank_transaction_evidence', 0);
        self::assertDatabaseCount('supplier_fuel_invoice_bank_payments', 0);
    }

    private function actor(Organization $organization): User
    {
        $actor = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $organization->id, 'user_id' => $actor->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->id);
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
        Sanctum::actingAs($actor);
        $this->withHeader('X-Organization-ID', (string) $organization->id);

        return $actor;
    }

    private function driver(string $suffix): Driver
    {
        return Driver::query()->create([
            'user_id' => User::factory()->create()->id, 'first_name' => 'S076', 'last_name' => $suffix,
            'license_number' => 'S076-'.Str::uuid(), 'license_category' => 'B', 'active' => true,
        ]);
    }

    private function organization(string $name, string $type): Organization
    {
        return Organization::query()->create(['name' => $name, 'type' => $type, 'status' => Organization::STATUS_ACTIVE]);
    }
}
