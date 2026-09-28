<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\BillingDocumentLine;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\OrganizationTaxProfile;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CustomerInvoiceIssuanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_issued_invoice_has_immutable_identity_and_idempotent_transition(): void
    {
        $user = User::factory()->create();
        $issuer = Organization::query()->create([
            'name' => 'Issuer', 'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE, 'vat_status' => 'payer',
            'registration_number' => '12345678', 'vat_number' => 'CZ12345678',
            'street' => 'Main 1', 'city' => 'Prague', 'postal_code' => '11000', 'country_code' => 'CZ',
        ]);
        $customer = Organization::query()->create([
            'name' => 'Customer', 'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE, 'vat_status' => 'payer',
            'registration_number' => '87654321', 'vat_number' => 'CZ87654321',
            'street' => 'Side 2', 'city' => 'Brno', 'postal_code' => '60200', 'country_code' => 'CZ',
        ]);
        OrganizationMembership::query()->create([
            'organization_id' => $issuer->id, 'user_id' => $user->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(), 'valid_until' => null,
        ]);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $issuer->id);
        $registrar->forgetCachedPermissions();
        $user->givePermissionTo(Permission::findOrCreate('compensation.manage', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
        $user->unsetRelation('permissions');
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $issuer->id);

        OrganizationTaxProfile::query()->create([
            'organization_id' => $issuer->id, 'vat_status' => 'payer',
            'vat_rate' => '21.00', 'valid_from' => '2026-09-01',
            'verified_at' => now(), 'created_by_user_id' => $user->id,
        ]);
        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->id, 'target_organization_id' => $issuer->id,
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-09-01', 'valid_until' => null,
        ]);
        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->id,
            'owner_organization_id' => $customer->id,
            'customer_organization_id' => $customer->id,
            'provider_organization_id' => $issuer->id,
            'name' => 'September customer tariff', 'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE, 'current_version' => 1,
            'created_by_user_id' => $user->id,
        ]);
        $version = PriceListVersion::query()->create([
            'price_list_id' => $priceList->id, 'version_number' => 1,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-09-01', 'created_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id, 'approved_at' => now(),
            'activated_at' => now(),
        ]);
        $driver = Driver::query()->create([
            'user_id' => $user->id, 'first_name' => 'Test', 'last_name' => 'Driver',
            'license_number' => 'INVOICE-TEST', 'active' => true,
        ]);
        $report = DailyReport::query()->create([
            'organization_id' => $customer->id,
            'performed_by_driver_id' => $driver->id,
            'entered_by_user_id' => $user->id,
            'route_number' => 'INV-01', 'route_number_normalized' => 'inv-01',
            'service_date' => '2026-09-10',
            'status' => DailyReport::STATUS_APPROVED,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
        ]);
        $calculation = FinancialCalculation::query()->create([
            'organization_id' => $issuer->id,
            'organization_relationship_id' => $relationship->id,
            'price_list_id' => $priceList->id,
            'price_list_version_id' => $version->id,
            'daily_report_id' => $report->id,
            'daily_report_version' => 1, 'calculation_version' => 1,
            'status' => FinancialCalculation::STATUS_APPROVED, 'currency' => 'CZK',
            'input_snapshot' => ['service_date' => '2026-09-10'],
            'subtotal_amount' => '100.00', 'total_amount' => '100.00',
            'calculated_by_user_id' => $user->id, 'calculated_at' => now(),
            'approved_by_user_id' => $user->id, 'approved_at' => now(),
        ]);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'customer_organization_id' => $customer->id,
            'period_from' => '2026-09-01', 'period_until' => '2026-09-30',
            'calculation_public_ids' => [$calculation->public_id],
        ];
        $url = '/api/v1/customer-invoices';
        $first = $this->postJson($url, $payload)->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.document_number', null)
            ->assertJsonPath('data.net_amount', '100.00')
            ->assertJsonPath('data.vat_amount', '21.00')
            ->assertJsonPath('data.gross_amount', '121.00')
            ->assertJsonPath('data.lines.0.calculation_public_id', $calculation->public_id);
        $this->postJson($url, $payload)->assertCreated()
            ->assertJsonPath('data.public_id', $first->json('data.public_id'));
        self::assertSame(1, BillingDocument::query()->count());
        self::assertSame(1, BillingDocumentLine::query()->count());

        $invoiceId = $first->json('data.public_id');
        $issuePayload = [
            'idempotency_key' => (string) Str::uuid(),
            'document_number' => 'FV-2026-001', 'variable_symbol' => '2026001',
            'issued_on' => '2026-09-28', 'taxable_supply_on' => '2026-09-10',
            'due_on' => '2026-10-14', 'reason' => 'Approved for customer billing.',
        ];
        $this->postJson('/api/v1/invoice-payment-account', [
            'iban' => 'CZ0008000000192000145399', 'account_holder' => 'Issuer',
            'source_reference' => 'Invalid account', 'reason' => 'Reject invalid check digits.',
        ])->assertUnprocessable()->assertJsonValidationErrors('iban');
        $this->postJson('/api/v1/invoice-payment-account', [
            'iban' => 'CZ6508000000192000145399', 'account_holder' => 'Issuer',
            'source_reference' => 'Bank contract 2026', 'reason' => 'Confirmed new invoice account.',
        ])->assertCreated()->assertJsonPath('data.revision', 1);
        $issueUrl = $url.'/'.$invoiceId.'/issue';
        $this->postJson($issueUrl, $issuePayload)->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.document_number', 'FV-2026-001')
            ->assertJsonPath('data.content.gross_amount', '121.00')
            ->assertJsonPath('data.content.payment_iban', 'CZ6508000000192000145399')
            ->assertJsonPath('data.content.lines.0.gross_amount', '121.00');
        $this->postJson($issueUrl, $issuePayload)->assertOk()
            ->assertJsonPath('data.document_number', 'FV-2026-001');
        self::assertSame(1, BillingDocumentCommercialIdentity::query()->count());
        self::assertSame(1, BillingDocumentCommercialIdentityEvent::query()->count());
        $identity = BillingDocumentCommercialIdentity::query()->firstOrFail();
        self::assertSame('approved', BillingDocument::query()->firstOrFail()->status);
        self::assertSame('FV-2026-001', $identity->document_number);
        $this->getJson($url.'/'.$invoiceId)->assertOk()
            ->assertJsonPath('data.customer.street', 'Side 2')
            ->assertJsonPath('data.status', 'approved');
        $this->get($url.'/'.$invoiceId.'/document')->assertOk()
            ->assertSee('FV-2026-001')
            ->assertSee('Side 2')
            ->assertSee('121.00')
            ->assertSee('2026001');
        $this->postJson('/api/v1/invoice-payment-account', [
            'iban' => 'CZ0008000000192000145399', 'account_holder' => 'Issuer',
            'source_reference' => 'Invalid account', 'reason' => 'Reject invalid check digits.',
        ])->assertUnprocessable()->assertJsonValidationErrors('iban');
        $this->postJson('/api/v1/invoice-payment-account', [
            'iban' => 'CZ5508000000001234567899', 'account_holder' => 'Issuer',
            'source_reference' => 'Bank contract 2026 revision', 'reason' => 'Confirmed a later bank account.',
        ])->assertCreated()->assertJsonPath('data.revision', 2);
        $this->get($url.'/'.$invoiceId.'/document')->assertOk()
            ->assertSee('CZ6508000000192000145399')->assertDontSee('CZ5508000000001234567899');
        Storage::fake('local');
        $firstPdf = $this->get($url.'/'.$invoiceId.'/pdf')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $pdfBytes = $firstPdf->getContent();
        self::assertIsString($pdfBytes);
        self::assertStringStartsWith('%PDF', $pdfBytes);
        $artifact = CustomerInvoicePdfArtifact::query()->sole();
        self::assertSame(hash('sha256', $pdfBytes), $artifact->pdf_sha256);
        self::assertSame($pdfBytes, Storage::disk('local')->get($artifact->storage_path));
        self::assertSame($pdfBytes, $this->get($url.'/'.$invoiceId.'/pdf')->assertOk()->getContent());
        $customer->forceFill(['street' => 'Changed 99'])->save();
        $this->getJson($url.'/'.$invoiceId)->assertOk()
            ->assertJsonPath('data.customer.street', 'Side 2');
        $this->get($url.'/'.$invoiceId.'/document')->assertOk()
            ->assertSee('Side 2')->assertDontSee('Changed 99');
        $customerUser = User::factory()->create();
        OrganizationMembership::query()->create([
            'organization_id' => $customer->id, 'user_id' => $customerUser->id,
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(), 'valid_until' => null,
        ]);
        $registrar->setPermissionsTeamId((int) $customer->id);
        $registrar->forgetCachedPermissions();
        $customerUser->givePermissionTo(Permission::findOrCreate('compensation.view', 'web'));
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
        $customerUser->unsetRelation('permissions');
        Sanctum::actingAs($customerUser);
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->getJson($url.'/'.$invoiceId)->assertOk()
            ->assertJsonPath('data.document_number', 'FV-2026-001')
            ->assertJsonPath('data.customer.street', 'Side 2');
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->get($url.'/'.$invoiceId.'/document')->assertOk()
            ->assertSee('FV-2026-001');
        self::assertSame($pdfBytes, $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->get($url.'/'.$invoiceId.'/pdf')->assertOk()->getContent());
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $issuer->id);
        Storage::disk('local')->put($artifact->storage_path, 'tampered');
        $this->get($url.'/'.$invoiceId.'/pdf')->assertStatus(409);
        $issuePayload['document_number'] = 'FV-2026-002';
        $this->postJson($issueUrl, $issuePayload)->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
        self::assertSame(1, BillingDocumentCommercialIdentity::query()->count());

        $payload['idempotency_key'] = (string) Str::uuid();
        $this->postJson($url, $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('calculation_public_ids');
        self::assertSame(1, BillingDocument::query()->count());
    }
}
