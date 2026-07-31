<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PriceListVersionActivationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_activate_price_list_version(): void
    {
        [$creator, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Guest activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $creator,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        $this->postJson(
            $this->activateUrl($priceList, $version),
            $this->activatePayload(),
        )->assertUnauthorized();

        $this->assertApprovedPreserved($priceList, $version);
    }

    public function test_organization_context_is_required_for_version_activation(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->postJson(
            $this->activateUrl($priceList, $version),
            $this->activatePayload(),
        )->assertStatus(400);

        $this->assertApprovedPreserved($priceList, $version);
    }

    public function test_pricing_manage_permission_is_required_for_version_activation(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertForbidden();

        $this->assertApprovedPreserved($priceList, $version);
    }

    public function test_it_activates_current_approved_version_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Successful activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_DRAFT,
            1,
            1,
            3,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        $approvedAt = $version->getAttribute('approved_at');

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(3),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Price list version activated.',
            )
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.lock_version', 3)
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_ACTIVE,
            )
            ->assertJsonPath('data.valid_from', '2026-08-01')
            ->assertJsonPath('data.valid_until', '2026-12-31')
            ->assertJsonPath(
                'data.change_reason',
                'Activation candidate',
            )
            ->assertJsonCount(4, 'data.items');

        $responseData = $response->json('data');

        self::assertIsArray($responseData);
        self::assertIsString($responseData['approved_at'] ?? null);
        self::assertIsString($responseData['activated_at'] ?? null);
        self::assertArrayNotHasKey('id', $responseData);
        self::assertArrayNotHasKey(
            'price_list_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'approved_by_user_id',
            $responseData,
        );

        $priceList->refresh();
        $version->refresh();

        self::assertSame(
            PriceList::STATUS_ACTIVE,
            $priceList->getAttribute('status'),
        );
        self::assertSame(
            1,
            $priceList->getAttribute('current_version'),
        );
        self::assertSame(
            PriceListVersion::STATUS_ACTIVE,
            $version->getAttribute('status'),
        );
        self::assertSame(
            1,
            $version->getAttribute('version_number'),
        );
        self::assertSame(
            3,
            $version->getAttribute('lock_version'),
        );
        self::assertSame(
            $user->getKey(),
            $version->getAttribute('approved_by_user_id'),
        );
        self::assertEquals(
            $approvedAt,
            $version->getAttribute('approved_at'),
        );
        self::assertInstanceOf(
            DateTimeInterface::class,
            $version->getAttribute('activated_at'),
        );

        $this->assertDatabaseHas('price_lists', [
            'id' => $priceList->getKey(),
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,
        ]);

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $version->getKey(),
            'version_number' => 1,
            'lock_version' => 3,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'approved_by_user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseCount('price_list_items', 4);
    }

    public function test_stale_lock_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Stale activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_DRAFT,
            1,
            1,
            2,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(1),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price-list approved version has changed.',
            );

        $this->assertApprovedPreserved(
            $priceList,
            $version,
            2,
        );
    }

    public function test_only_current_version_may_be_activated(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Non-current activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_DRAFT,
            2,
        );

        $current = $priceList->versions()->create([
            'version_number' => 2,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_APPROVED,
            'valid_from' => '2027-01-01',
            'valid_until' => null,
            'change_reason' => 'Current approved version',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now()->subHour(),
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only the current price-list version may be activated.',
            );

        $this->assertApprovedPreserved(
            $priceList,
            $version,
            1,
            2,
        );

        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $current->refresh()->getAttribute('status'),
        );
        self::assertNull(
            $current->getAttribute('activated_at'),
        );
    }

    public function test_only_approved_version_may_be_activated(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Draft activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
        );

        $version->update([
            'status' => PriceListVersion::STATUS_DRAFT,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only approved price-list versions may be activated.',
            );

        $version->refresh();

        self::assertSame(
            PriceListVersion::STATUS_DRAFT,
            $version->getAttribute('status'),
        );
        self::assertNull(
            $version->getAttribute('approved_by_user_id'),
        );
        self::assertNull(
            $version->getAttribute('approved_at'),
        );
        self::assertNull(
            $version->getAttribute('activated_at'),
        );
        self::assertSame(
            PriceList::STATUS_DRAFT,
            $priceList->refresh()->getAttribute('status'),
        );
    }

    public function test_valid_from_is_required_before_activation(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-period activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_DRAFT,
            1,
            1,
            1,
            null,
            null,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'A valid effective period is required before activation.',
            );

        $this->assertApprovedPreserved($priceList, $version);
    }

    public function test_existing_active_version_defers_replacement(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Deferred replacement provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_ACTIVE,
            2,
            2,
        );

        $activeVersion = $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-07-31',
            'change_reason' => 'Existing active version',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now()->subDays(2),
            'activated_at' => now()->subDay(),
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Price-list version replacement remains deferred.',
            );

        $this->assertApprovedPreserved(
            $priceList,
            $version,
            1,
            2,
            PriceList::STATUS_ACTIVE,
        );

        self::assertSame(
            PriceListVersion::STATUS_ACTIVE,
            $activeVersion->refresh()->getAttribute('status'),
        );
        self::assertInstanceOf(
            DateTimeInterface::class,
            $activeVersion->getAttribute('activated_at'),
        );
    }

    public function test_archived_price_list_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Archived activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_ARCHIVED,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Archived price lists cannot activate versions.',
            );

        $this->assertApprovedPreserved(
            $priceList,
            $version,
            1,
            1,
            PriceList::STATUS_ARCHIVED,
        );
    }

    public function test_version_activation_is_owner_organization_scoped(): void
    {
        [$user, $authorizedCustomer] = $this->createContext();

        $foreignCustomer = $this->createOrganization(
            'Foreign activation customer',
            Organization::TYPE_CARRIER,
        );

        $foreignProvider = $this->createOrganization(
            'Foreign activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $foreignCustomer,
            $foreignProvider,
        );

        $this->grantManagePermission(
            $user,
            $authorizedCustomer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($authorizedCustomer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                $this->activatePayload(),
            )
            ->assertNotFound();

        $this->assertApprovedPreserved($priceList, $version);
    }

    public function test_request_validates_expected_lock_version(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Validation activation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createApprovedAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->activateUrl($priceList, $version),
                [
                    'expected_lock_version' => 0,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'expected_lock_version',
            ]);

        $this->assertApprovedPreserved($priceList, $version);
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(): array
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Price-list version activation context',
            Organization::TYPE_CARRIER,
        );

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => (
                OrganizationMembership::RELATIONSHIP_EMPLOYEE
            ),
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        return [
            $user,
            $organization,
        ];
    }

    private function createOrganization(
        string $name,
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createRelationship(
        Organization $customer,
        Organization $provider,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->getKey(),
            'target_organization_id' => $provider->getKey(),
            'relationship_type' => (
                OrganizationRelationship::TYPE_SUBCONTRACTING
            ),
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);
    }

    /**
     * @return array{PriceList, PriceListVersion}
     */
    private function createApprovedAggregate(
        User $creator,
        Organization $customer,
        Organization $provider,
        string $priceListStatus = PriceList::STATUS_DRAFT,
        int $currentVersion = 1,
        int $versionNumber = 1,
        int $lockVersion = 1,
        ?string $validFrom = '2026-08-01',
        ?string $validUntil = '2026-12-31',
    ): array {
        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => (
                $relationship->getKey()
            ),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Version activation test',
            'description' => null,
            'currency' => 'CZK',
            'status' => $priceListStatus,
            'current_version' => $currentVersion,
            'created_by_user_id' => $creator->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => $versionNumber,
            'lock_version' => $lockVersion,
            'status' => PriceListVersion::STATUS_APPROVED,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'change_reason' => 'Activation candidate',
            'created_by_user_id' => $creator->getKey(),
            'approved_by_user_id' => $creator->getKey(),
            'approved_at' => now()->subDay(),
            'activated_at' => null,
        ]);

        return [
            $priceList,
            $version,
        ];
    }

    private function seedCanonicalItems(
        PriceListVersion $version,
    ): void {
        $rates = [
            PriceListItem::CODE_DELIVERED_PARCELS => '12.5000',
            PriceListItem::CODE_REDIRECTED_PARCELS => '8.0000',
            PriceListItem::CODE_UNDELIVERED_PARCELS => '3.0000',
            PriceListItem::CODE_ACTUAL_KM => '5.2500',
        ];

        $descriptions = [
            PriceListItem::CODE_DELIVERED_PARCELS => 'Delivered parcel',
            PriceListItem::CODE_REDIRECTED_PARCELS => 'Redirected parcel',
            PriceListItem::CODE_UNDELIVERED_PARCELS => 'Undelivered parcel',
            PriceListItem::CODE_ACTUAL_KM => 'Actual kilometre',
        ];

        foreach (PriceListItem::CODES as $index => $code) {
            $version->items()->create([
                'code' => $code,
                'description' => $descriptions[$code],
                'calculation_method' => (
                    PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
                ),
                'unit' => (
                    $code === PriceListItem::CODE_ACTUAL_KM
                        ? PriceListItem::UNIT_KM
                        : PriceListItem::UNIT_PARCEL
                ),
                'unit_rate' => $rates[$code],
                'currency' => 'CZK',
                'quantity_source' => $code,
                'rounding_scale' => 2,
                'rounding_method' => (
                    PriceListItem::ROUNDING_METHOD_HALF_UP
                ),
                'position' => $index + 1,
            ]);
        }
    }

    private function assertApprovedPreserved(
        PriceList $priceList,
        PriceListVersion $version,
        int $expectedLockVersion = 1,
        int $expectedCurrentVersion = 1,
        string $expectedPriceListStatus = PriceList::STATUS_DRAFT,
    ): void {
        $priceList->refresh();
        $version->refresh();

        self::assertSame(
            $expectedPriceListStatus,
            $priceList->getAttribute('status'),
        );
        self::assertSame(
            $expectedCurrentVersion,
            $priceList->getAttribute('current_version'),
        );
        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $version->getAttribute('status'),
        );
        self::assertSame(
            $expectedLockVersion,
            $version->getAttribute('lock_version'),
        );
        self::assertNotNull(
            $version->getAttribute('approved_by_user_id'),
        );
        self::assertInstanceOf(
            DateTimeInterface::class,
            $version->getAttribute('approved_at'),
        );
        self::assertNull(
            $version->getAttribute('activated_at'),
        );
    }

    private function activateUrl(
        PriceList $priceList,
        PriceListVersion $version,
    ): string {
        return sprintf(
            '/api/v1/price-lists/%s/versions/%d/activate',
            (string) $priceList->getAttribute('public_id'),
            (int) $version->getAttribute('version_number'),
        );
    }

    /**
     * @return array<string, int>
     */
    private function activatePayload(
        int $expectedLockVersion = 1,
    ): array {
        return [
            'expected_lock_version' => $expectedLockVersion,
        ];
    }

    private function grantManagePermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            $registrar->forgetCachedPermissions();

            $permission = Permission::findOrCreate(
                'pricing.manage',
                'web',
            );

            $user->givePermissionTo($permission);
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    private function withOrganization(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
