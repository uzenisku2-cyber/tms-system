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

final class PriceListVersionApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_approve_price_list_version(): void
    {
        [$creator, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Guest approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $creator,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        $this->postJson(
            $this->approveUrl($priceList, $version),
            $this->approvePayload(),
        )->assertUnauthorized();

        $this->assertDraftPreserved($priceList, $version);
    }

    public function test_organization_context_is_required_for_version_approval(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->postJson(
            $this->approveUrl($priceList, $version),
            $this->approvePayload(),
        )->assertStatus(400);

        $this->assertDraftPreserved($priceList, $version);
    }

    public function test_pricing_manage_permission_is_required_for_version_approval(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertForbidden();

        $this->assertDraftPreserved($priceList, $version);
    }

    public function test_it_approves_current_complete_draft_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Successful approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
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

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(3),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Price list version approved.',
            )
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.lock_version', 3)
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_APPROVED,
            )
            ->assertJsonPath('data.activated_at', null)
            ->assertJsonCount(4, 'data.items');

        $responseData = $response->json('data');

        self::assertIsArray($responseData);
        self::assertIsString($responseData['approved_at'] ?? null);
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
            PriceList::STATUS_DRAFT,
            $priceList->getAttribute('status'),
        );
        self::assertSame(
            1,
            $priceList->getAttribute('current_version'),
        );
        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
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
        self::assertInstanceOf(
            DateTimeInterface::class,
            $version->getAttribute('approved_at'),
        );
        self::assertNull(
            $version->getAttribute('activated_at'),
        );

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $version->getKey(),
            'version_number' => 1,
            'lock_version' => 3,
            'status' => PriceListVersion::STATUS_APPROVED,
            'approved_by_user_id' => $user->getKey(),
            'activated_at' => null,
        ]);

        $this->assertDatabaseCount('price_list_items', 4);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'unit_rate' => '12.5000',
            'position' => 1,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
            'unit_rate' => '8.0000',
            'position' => 2,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
            'unit_rate' => '3.0000',
            'position' => 3,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'unit_rate' => '5.2500',
            'position' => 4,
        ]);
    }

    public function test_stale_lock_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Stale approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
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
                $this->approveUrl($priceList, $version),
                $this->approvePayload(1),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price-list draft version has changed.',
            );

        $this->assertDraftPreserved(
            $priceList,
            $version,
            2,
        );
    }

    public function test_only_current_version_may_be_approved(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Non-current approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_ACTIVE,
            2,
        );

        $this->seedCanonicalItems($version);

        $current = $priceList->versions()->create([
            'version_number' => 2,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_APPROVED,
            'valid_from' => '2026-09-01',
            'valid_until' => null,
            'change_reason' => 'Current approved version',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now()->subDay(),
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only the current price-list version may be approved.',
            );

        $this->assertDraftPreserved(
            $priceList,
            $version,
            1,
            2,
        );

        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $current->refresh()->getAttribute('status'),
        );
    }

    public function test_non_draft_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Approved approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        $version->update([
            'status' => PriceListVersion::STATUS_APPROVED,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now()->subDay(),
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertStatus(409);

        $version->refresh();

        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $version->getAttribute('status'),
        );
        self::assertSame(
            1,
            $version->getAttribute('lock_version'),
        );
        self::assertSame(
            $user->getKey(),
            $version->getAttribute('approved_by_user_id'),
        );
        self::assertInstanceOf(
            DateTimeInterface::class,
            $version->getAttribute('approved_at'),
        );
        self::assertNull(
            $version->getAttribute('activated_at'),
        );

        $this->assertDatabaseCount('price_list_items', 4);
    }

    public function test_complete_canonical_pricing_item_set_is_required(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Incomplete approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version, 3);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                (
                    'The complete canonical pricing-item set is required '.
                    'before approval.'
                ),
            );

        $this->assertDraftPreserved($priceList, $version);
        $this->assertDatabaseCount('price_list_items', 3);
    }

    public function test_archived_price_list_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Archived approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_ARCHIVED,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertStatus(409);

        $this->assertDraftPreserved($priceList, $version);

        self::assertSame(
            PriceList::STATUS_ARCHIVED,
            $priceList->refresh()->getAttribute('status'),
        );
    }

    public function test_version_approval_is_owner_organization_scoped(): void
    {
        [$user, $authorizedCustomer] = $this->createContext();

        $foreignCustomer = $this->createOrganization(
            'Foreign approval customer',
            Organization::TYPE_CARRIER,
        );

        $foreignProvider = $this->createOrganization(
            'Foreign approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $foreignCustomer,
            $foreignProvider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission(
            $user,
            $authorizedCustomer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($authorizedCustomer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                $this->approvePayload(),
            )
            ->assertNotFound();

        $this->assertDraftPreserved($priceList, $version);
    }

    public function test_request_validates_expected_lock_version(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Validation approval provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->approveUrl($priceList, $version),
                [
                    'expected_lock_version' => 0,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'expected_lock_version',
            ]);

        $this->assertDraftPreserved($priceList, $version);
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(): array
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Price-list version approval context',
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
    private function createDraftAggregate(
        User $creator,
        Organization $customer,
        Organization $provider,
        string $priceListStatus = PriceList::STATUS_DRAFT,
        int $currentVersion = 1,
        int $versionNumber = 1,
        int $lockVersion = 1,
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
            'name' => 'Version approval test',
            'description' => null,
            'currency' => 'CZK',
            'status' => $priceListStatus,
            'current_version' => $currentVersion,
            'created_by_user_id' => $creator->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => $versionNumber,
            'lock_version' => $lockVersion,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-08-01',
            'valid_until' => '2026-12-31',
            'change_reason' => 'Approval candidate',
            'created_by_user_id' => $creator->getKey(),
        ]);

        return [
            $priceList,
            $version,
        ];
    }

    private function seedCanonicalItems(
        PriceListVersion $version,
        int $limit = 4,
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

        foreach (
            array_slice(
                PriceListItem::CODES,
                0,
                $limit,
            ) as $index => $code
        ) {
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

    private function assertDraftPreserved(
        PriceList $priceList,
        PriceListVersion $version,
        int $expectedLockVersion = 1,
        int $expectedCurrentVersion = 1,
    ): void {
        $priceList->refresh();
        $version->refresh();

        self::assertSame(
            $expectedCurrentVersion,
            $priceList->getAttribute('current_version'),
        );
        self::assertSame(
            PriceListVersion::STATUS_DRAFT,
            $version->getAttribute('status'),
        );
        self::assertSame(
            $expectedLockVersion,
            $version->getAttribute('lock_version'),
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
    }

    private function approveUrl(
        PriceList $priceList,
        PriceListVersion $version,
    ): string {
        return sprintf(
            '/api/v1/price-lists/%s/versions/%d/approve',
            (string) $priceList->getAttribute('public_id'),
            (int) $version->getAttribute('version_number'),
        );
    }

    /**
     * @return array<string, int>
     */
    private function approvePayload(
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
