<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\BankTransactionEvidence;
use App\Modules\Fleet\Services\BankTransactionEvidenceCapacityService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Jobs\SendCustomerInvoiceEmailJob;
use App\Modules\Pricing\Mail\CustomerInvoiceEmail;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentity;
use App\Modules\Pricing\Models\BillingDocumentCommercialIdentityEvent;
use App\Modules\Pricing\Models\BillingDocumentLine;
use App\Modules\Pricing\Models\CustomerInvoiceBankPayment;
use App\Modules\Pricing\Models\CustomerInvoiceBankPaymentEvent;
use App\Modules\Pricing\Models\CustomerInvoiceDeliveryEvent;
use App\Modules\Pricing\Models\CustomerInvoiceEmailDispatch;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\OrganizationTaxProfile;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        $bank = BankTransactionEvidence::query()->create([
            'public_id' => (string) Str::uuid(), 'organization_context_id' => $issuer->id,
            'idempotency_key' => (string) Str::uuid(), 'source_type' => 'manual_evidence',
            'source_reference' => 'BANK-INVOICE-001', 'bank_statement_reference' => 'SEP-2026',
            'direction' => 'credit', 'booked_at' => '2026-09-28', 'value_date' => '2026-09-28',
            'amount' => '121.00', 'currency' => 'CZK', 'account_identifier' => 'CZ6508000000192000145399',
            'counterparty_name' => 'Customer', 'counterparty_account_identifier' => 'CZ6508000000000000000001',
            'variable_symbol' => '2026001', 'message' => 'Invoice payment', 'evidence_note' => 'Bank statement reference.',
            'status' => 'recorded', 'recorded_by_user_id' => $user->id, 'recorded_at' => now(), 'revision' => 1,
        ]);
        $paymentUrl = $url.'/'.$invoiceId.'/bank-payments';
        $this->getJson($paymentUrl)->assertOk()->assertJsonPath('data.payment_state', 'unpaid');
        $allocation = [
            'idempotency_key' => (string) Str::uuid(), 'bank_transaction_evidence_public_id' => $bank->public_id,
            'expected_bank_revision' => 1, 'allocated_amount_minor' => 5000,
            'reason' => 'Checked bank credit against issued invoice identity.',
        ];
        foreach (['direction' => 'debit', 'currency' => 'EUR', 'variable_symbol' => '999999',
            'account_identifier' => 'CZ5508000000001234567899'] as $field => $wrong) {
            $invalidBank = $bank->replicate();
            $invalidBank->forceFill([
                'public_id' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(),
                'source_reference' => 'BANK-INVOICE-INVALID-'.Str::uuid(), $field => $wrong,
            ])->save();
            $this->postJson($paymentUrl, array_replace($allocation, [
                'idempotency_key' => (string) Str::uuid(),
                'bank_transaction_evidence_public_id' => $invalidBank->public_id,
            ]))
                ->assertUnprocessable()->assertJsonValidationErrors('bank_transaction_evidence_public_id');
        }
        $this->postJson($paymentUrl, array_replace($allocation, ['expected_bank_revision' => 2]))
            ->assertUnprocessable()->assertJsonValidationErrors('expected_bank_revision');
        $this->postJson($paymentUrl, array_replace($allocation, ['idempotency_key' => (string) Str::uuid(), 'allocated_amount_minor' => 12200]))
            ->assertUnprocessable()->assertJsonValidationErrors('allocated_amount_minor');
        $firstPayment = $this->postJson($paymentUrl, $allocation)->assertCreated()->json('data');
        $this->postJson($paymentUrl, $allocation)->assertCreated()->assertJsonPath('data.public_id', $firstPayment['public_id']);
        $this->postJson($paymentUrl, array_replace($allocation, ['allocated_amount_minor' => 5100]))
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->getJson($paymentUrl)->assertOk()->assertJsonPath('data.paid_amount_minor', 5000)
            ->assertJsonPath('data.unpaid_amount_minor', 7100)->assertJsonPath('data.payment_state', 'partially_paid');
        $receivablesUrl = '/api/v1/customer-receivables';
        $this->getJson($receivablesUrl.'?state=invalid')->assertUnprocessable()
            ->assertJsonValidationErrors('state');
        $this->getJson($receivablesUrl.'?as_of=2026-10-15&state=overdue')->assertOk()
            ->assertJsonPath('data.balance_basis', 'current_active_allocations')
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.public_id', $invoiceId)
            ->assertJsonPath('data.items.0.unpaid_amount_minor', 7100)
            ->assertJsonPath('data.items.0.overdue_days', 1)
            ->assertJsonPath('data.customer_totals.0.overdue_amount_minor', 7100);
        $this->getJson($receivablesUrl.'?as_of=2026-10-14&state=overdue')->assertOk()
            ->assertJsonPath('data.pagination.total', 0);
        $this->getJson($receivablesUrl.'?customer_organization_id='.$issuer->id)->assertOk()
            ->assertJsonPath('data.pagination.total', 0);
        self::assertSame(7100, app(BankTransactionEvidenceCapacityService::class)->remainingMinor($bank));
        $second = array_replace($allocation, ['idempotency_key' => (string) Str::uuid(), 'allocated_amount_minor' => 7100]);
        $this->postJson($paymentUrl, $second)->assertCreated();
        $this->getJson($paymentUrl)->assertOk()->assertJsonPath('data.payment_state', 'paid')
            ->assertJsonPath('data.unpaid_amount_minor', 0);
        $this->getJson($receivablesUrl.'?as_of=2026-10-15&state=paid')->assertOk()
            ->assertJsonPath('data.items.0.paid_amount_minor', 12100)
            ->assertJsonPath('data.items.0.overdue_days', 0);
        $this->postJson($paymentUrl, array_replace($second, ['idempotency_key' => (string) Str::uuid()]))
            ->assertUnprocessable()->assertJsonValidationErrors('allocated_amount_minor');
        $reverseUrl = $paymentUrl.'/'.$firstPayment['public_id'].'/reverse';
        $reverse = ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
            'reason' => 'Reversed erroneous first allocation after checking statement.'];
        $this->postJson($reverseUrl, $reverse)->assertOk()->assertJsonPath('data.status', 'reversed');
        $this->postJson($reverseUrl, $reverse)->assertOk()->assertJsonPath('data.revision', 2);
        $this->getJson($paymentUrl)->assertOk()->assertJsonPath('data.payment_state', 'partially_paid')
            ->assertJsonPath('data.unpaid_amount_minor', 5000);
        $this->getJson($receivablesUrl.'?as_of=2026-10-15&state=overdue')->assertOk()
            ->assertJsonPath('data.items.0.unpaid_amount_minor', 5000);
        self::assertSame(5000, app(BankTransactionEvidenceCapacityService::class)->remainingMinor($bank));
        self::assertSame(2, CustomerInvoiceBankPayment::query()->count());
        self::assertSame(3, CustomerInvoiceBankPaymentEvent::query()->count());
        self::assertSame('121.00', (string) BillingDocument::query()->firstOrFail()->gross_amount);
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
        $deliveryUrl = $url.'/'.$invoiceId.'/deliveries';
        $delivery = [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 0,
            'pdf_sha256' => $artifact->pdf_sha256, 'method' => 'email',
            'recipient' => 'billing@example.test', 'delivered_at' => now()->subMinute()->toIso8601String(),
            'evidence_reference' => 'Outbound message INV-2026-001',
            'reason' => 'Recorded the dispatch evidence after sending the PDF.',
        ];
        $this->postJson($deliveryUrl, $delivery)->assertCreated()
            ->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.pdf_sha256', $artifact->pdf_sha256);
        $this->postJson($deliveryUrl, $delivery)->assertCreated()->assertJsonPath('data.revision', 1);
        $this->getJson($deliveryUrl)->assertOk()->assertJsonPath('data.current_revision', 1)
            ->assertJsonPath('data.events.0.evidence_reference', $delivery['evidence_reference']);
        $this->postJson($deliveryUrl, array_replace($delivery, ['recipient' => 'different@example.test']))
            ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $stale = array_replace($delivery, ['idempotency_key' => (string) Str::uuid()]);
        $this->postJson($deliveryUrl, $stale)->assertStatus(409);
        $correction = array_replace($stale, [
            'idempotency_key' => (string) Str::uuid(), 'expected_revision' => 1,
            'recipient' => 'corrected@example.test',
            'reason' => 'Corrected the recipient after checking the dispatch record.',
        ]);
        $this->postJson($deliveryUrl, $correction)->assertCreated()->assertJsonPath('data.revision', 2);
        self::assertSame(2, CustomerInvoiceDeliveryEvent::query()->count());
        $this->getJson($deliveryUrl)->assertOk()->assertJsonPath('data.current_revision', 2)
            ->assertJsonPath('data.events.0.recipient', 'billing@example.test')
            ->assertJsonPath('data.events.1.recipient', 'corrected@example.test');
        Queue::fake();
        Mail::fake();
        config()->set('mail.default', 'smtp');
        config()->set('mail.from.address', 'invoices@issuer.test');
        $emailUrl = $url.'/'.$invoiceId.'/email-dispatch';
        $email = [
            'idempotency_key' => (string) Str::uuid(), 'pdf_sha256' => $artifact->pdf_sha256,
            'recipient_email' => 'customer@example.test',
            'reason' => 'Send the approved invoice to the customer billing address.',
        ];
        $this->postJson($emailUrl, $email)->assertStatus(202)
            ->assertJsonPath('data.status', 'queued');
        $this->postJson($emailUrl, $email)->assertStatus(202)->assertJsonPath('data.status', 'queued');
        Queue::assertPushed(SendCustomerInvoiceEmailJob::class, 1);
        $this->postJson($emailUrl, array_replace($email, ['idempotency_key' => (string) Str::uuid()]))
            ->assertStatus(409);
        $dispatch = CustomerInvoiceEmailDispatch::query()->sole();
        (new SendCustomerInvoiceEmailJob($dispatch->id))->handle();
        Mail::assertSent(CustomerInvoiceEmail::class, 1);
        $this->getJson($emailUrl)->assertOk()->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.pdf_sha256', $artifact->pdf_sha256);
        (new SendCustomerInvoiceEmailJob($dispatch->id))->handle();
        Mail::assertSent(CustomerInvoiceEmail::class, 1);
        $this->getJson($deliveryUrl)->assertOk()->assertJsonPath('data.current_revision', 3)
            ->assertJsonPath('data.events.2.evidence_reference', 'email-dispatch:'.$dispatch->public_id);
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
            ->getJson($receivablesUrl)->assertForbidden();
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->getJson($paymentUrl)->assertForbidden();
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->postJson($paymentUrl, array_replace($allocation, ['idempotency_key' => (string) Str::uuid()]))->assertForbidden();
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->getJson($url.'/'.$invoiceId)->assertOk()
            ->assertJsonPath('data.document_number', 'FV-2026-001')
            ->assertJsonPath('data.customer.street', 'Side 2');
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->get($url.'/'.$invoiceId.'/document')->assertOk()
            ->assertSee('FV-2026-001');
        self::assertSame($pdfBytes, $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->get($url.'/'.$invoiceId.'/pdf')->assertOk()->getContent());
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->getJson($deliveryUrl)->assertOk()->assertJsonPath('data.current_revision', 3);
        $this->withHeader('X-Organization-ID', (string) $customer->id)
            ->postJson($deliveryUrl, array_replace($correction, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2]))
            ->assertForbidden();
        Sanctum::actingAs($user);
        $this->withHeader('X-Organization-ID', (string) $issuer->id);
        $this->getJson($emailUrl)->assertOk()->assertJsonPath('data.status', 'accepted');
        Storage::disk('local')->put($artifact->storage_path, 'tampered');
        $this->get($url.'/'.$invoiceId.'/pdf')->assertStatus(409);
        $this->postJson($deliveryUrl, array_replace($correction, ['idempotency_key' => (string) Str::uuid(), 'expected_revision' => 2]))->assertStatus(409);
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
