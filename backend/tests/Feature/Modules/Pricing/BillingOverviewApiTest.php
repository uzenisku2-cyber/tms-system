<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\BillingDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BillingOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/billing-overview';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_company_view_exposes_vat_and_net_margin_comparison(): void
    {
        $admin = User::factory()->create();
        $master = $this->organization('DRAYVIA', Organization::TYPE_MASTER, 'payer');
        $customer = $this->organization('Customer', Organization::TYPE_CARRIER, 'payer');
        $carrier = $this->organization('Non-payer carrier', Organization::TYPE_SUBCONTRACTOR, 'non_payer');
        $this->membership($admin, $master);
        $this->permission($admin, $master, 'compensation.view');
        $this->permission($admin, $master, 'compensation.manage');

        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Milos',
            'last_name' => 'Driver',
            'license_number' => null,
            'active' => true,
        ]);

        $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_CUSTOMER_INVOICE,
            '1000.00',
            '210.00',
            '1210.00',
            $customer,
        );
        $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            '400.00',
            '0.00',
            '400.00',
            $carrier,
        );
        $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_DRIVER_REMUNERATION,
            '250.00',
            '0.00',
            '250.00',
            null,
            $driver,
        );
        $historical = $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_CUSTOMER_INVOICE,
            '500.00',
            '105.00',
            '605.00',
            $customer,
        );
        $historical->forceFill([
            'period_from' => '2025-12-01',
            'period_until' => '2025-12-31',
        ])->save();

        Sanctum::actingAs($admin);

        $this->withOrganization($master)
            ->getJson(self::URL.'?period_from=2026-08-01&period_until=2026-08-31')
            ->assertOk()
            ->assertJsonPath('data.visibility', 'company')
            ->assertJsonPath('data.vat_breakdown_visible', true)
            ->assertJsonPath('data.margin_visible', true)
            ->assertJsonPath('data.summary.customer_billing.net', '1000.00')
            ->assertJsonPath('data.summary.customer_billing.vat', '210.00')
            ->assertJsonPath('data.summary.customer_billing.gross', '1210.00')
            ->assertJsonPath('data.summary.external_carrier_cost.vat', '0.00')
            ->assertJsonPath('data.summary.driver_cost.net', '250.00')
            ->assertJsonPath('data.summary.gross_margin_net', '350.00')
            ->assertJsonPath('data.available_periods.years', [2026, 2025])
            ->assertJsonPath('data.available_periods.months', ['2026-08', '2025-12'])
            ->assertJsonCount(3, 'data.items');
    }

    public function test_non_payer_carrier_receives_only_own_final_amount_without_vat_fields(): void
    {
        $admin = User::factory()->create();
        $master = $this->organization('DRAYVIA', Organization::TYPE_MASTER, 'payer');
        $carrier = $this->organization('Non-payer carrier', Organization::TYPE_SUBCONTRACTOR, 'non_payer');
        $otherCarrier = $this->organization('Other carrier', Organization::TYPE_SUBCONTRACTOR, 'non_payer');
        $carrierUser = User::factory()->create();
        $this->membership($carrierUser, $carrier);
        $this->permission($carrierUser, $carrier, 'compensation.view');

        $own = $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            '400.00',
            '0.00',
            '400.00',
            $carrier,
        );
        $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_EXTERNAL_CARRIER_SETTLEMENT,
            '900.00',
            '0.00',
            '900.00',
            $otherCarrier,
        );

        Sanctum::actingAs($carrierUser);

        $response = $this->withOrganization($carrier)
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.visibility', 'own')
            ->assertJsonPath('data.vat_breakdown_visible', false)
            ->assertJsonPath('data.margin_visible', false)
            ->assertJsonPath('data.summary', null)
            ->assertJsonPath('data.available_periods.years', [2026])
            ->assertJsonPath('data.available_periods.months', ['2026-08'])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.public_id', $own->getRouteKey())
            ->assertJsonPath('data.items.0.amount', '400.00');

        $payload = $response->json('data.items.0');
        self::assertIsArray($payload);

        foreach (['net_amount', 'vat_rate', 'vat_amount', 'gross_amount'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $payload);
        }
    }

    public function test_driver_receives_only_own_remuneration_without_vat_or_margin(): void
    {
        $admin = User::factory()->create();
        $driverUser = User::factory()->create();
        $otherDriverUser = User::factory()->create();
        $master = $this->organization('DRAYVIA', Organization::TYPE_MASTER, 'payer');
        $this->membership($driverUser, $master);
        $this->permission($driverUser, $master, 'compensation.view');

        $driver = $this->driver($driverUser, 'Own');
        $otherDriver = $this->driver($otherDriverUser, 'Other');
        $own = $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_DRIVER_REMUNERATION,
            '250.00',
            '0.00',
            '250.00',
            null,
            $driver,
        );
        $this->document(
            $admin,
            $master,
            BillingDocument::TYPE_DRIVER_REMUNERATION,
            '300.00',
            '0.00',
            '300.00',
            null,
            $otherDriver,
        );

        Sanctum::actingAs($driverUser);

        $this->withOrganization($master)
            ->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.visibility', 'own')
            ->assertJsonPath('data.summary', null)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.public_id', $own->getRouteKey())
            ->assertJsonPath('data.items.0.amount', '250.00')
            ->assertJsonMissingPath('data.items.0.vat_amount')
            ->assertJsonMissingPath('data.items.0.gross_amount');
    }

    private function organization(string $name, string $type, string $vatStatus): Organization
    {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
            'vat_status' => $vatStatus,
        ]);
    }

    private function driver(User $user, string $firstName): Driver
    {
        return Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => $firstName,
            'last_name' => 'Driver',
            'license_number' => null,
            'active' => true,
        ]);
    }

    private function membership(User $user, Organization $organization): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);
    }

    private function permission(User $user, Organization $organization, string $name): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId((int) $organization->getKey());
            $registrar->forgetCachedPermissions();
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }

    private function document(
        User $actor,
        Organization $owner,
        string $type,
        string $net,
        string $vat,
        string $gross,
        ?Organization $counterparty = null,
        ?Driver $driver = null,
    ): BillingDocument {
        $standardVat = $type === BillingDocument::TYPE_CUSTOMER_INVOICE;

        return BillingDocument::query()->create([
            'owner_organization_id' => $owner->getKey(),
            'counterparty_organization_id' => $counterparty?->getKey(),
            'driver_id' => $driver?->getKey(),
            'document_type' => $type,
            'period_from' => '2026-08-01',
            'period_until' => '2026-08-31',
            'currency' => 'CZK',
            'vat_treatment' => $standardVat
                ? BillingDocument::VAT_STANDARD
                : BillingDocument::VAT_NOT_APPLICABLE,
            'vat_status_snapshot' => $standardVat ? 'payer' : 'non_payer',
            'net_amount' => $net,
            'vat_rate' => $standardVat ? '21.00' : null,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'status' => 'draft',
            'source_snapshot' => ['test' => true],
            'created_by_user_id' => $actor->getKey(),
        ]);
    }

    private function withOrganization(Organization $organization): static
    {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
