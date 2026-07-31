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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PriceListVersionWriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_update_price_list_version(): void
    {
        [$creator, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Guest update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $creator,
            $customer,
            $provider,
        );

        $this->putJson(
            $this->updateUrl($priceList, $version),
            $this->updatePayload(),
        )->assertUnauthorized();

        self::assertSame(
            1,
            $version->refresh()->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    public function test_organization_context_is_required_for_version_update(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->putJson(
            $this->updateUrl($priceList, $version),
            $this->updatePayload(),
        )->assertStatus(400);

        self::assertSame(
            1,
            $version->refresh()->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    public function test_pricing_manage_permission_is_required_for_version_update(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $this->updatePayload(),
            )
            ->assertForbidden();

        self::assertSame(
            1,
            $version->refresh()->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    public function test_it_replaces_draft_items_and_increments_lock_version(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Successful update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedOldItem($version);

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $this->updatePayload(),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Price list draft version updated.',
            )
            ->assertJsonPath(
                'data.version_number',
                1,
            )
            ->assertJsonPath(
                'data.lock_version',
                2,
            )
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_DRAFT,
            )
            ->assertJsonPath(
                'data.valid_from',
                '2026-08-01',
            )
            ->assertJsonPath(
                'data.valid_until',
                '2026-12-31',
            )
            ->assertJsonPath(
                'data.change_reason',
                'Updated draft rates',
            )
            ->assertJsonCount(
                4,
                'data.items',
            )
            ->assertJsonPath(
                'data.items.0.code',
                PriceListItem::CODE_DELIVERED_PARCELS,
            )
            ->assertJsonPath(
                'data.items.0.unit_rate',
                '12.5000',
            )
            ->assertJsonPath(
                'data.items.0.unit',
                PriceListItem::UNIT_PARCEL,
            )
            ->assertJsonPath(
                'data.items.0.position',
                1,
            )
            ->assertJsonPath(
                'data.items.1.code',
                PriceListItem::CODE_REDIRECTED_PARCELS,
            )
            ->assertJsonPath(
                'data.items.1.position',
                2,
            )
            ->assertJsonPath(
                'data.items.2.code',
                PriceListItem::CODE_UNDELIVERED_PARCELS,
            )
            ->assertJsonPath(
                'data.items.2.position',
                3,
            )
            ->assertJsonPath(
                'data.items.3.code',
                PriceListItem::CODE_ACTUAL_KM,
            )
            ->assertJsonPath(
                'data.items.3.unit',
                PriceListItem::UNIT_KM,
            )
            ->assertJsonPath(
                'data.items.3.unit_rate',
                '5.2500',
            )
            ->assertJsonPath(
                'data.items.3.position',
                4,
            );

        $firstItem = $response->json('data.items.0');

        self::assertIsArray($firstItem);
        self::assertArrayNotHasKey('id', $firstItem);
        self::assertArrayNotHasKey(
            'price_list_version_id',
            $firstItem,
        );

        $version->refresh();
        $priceList->refresh();

        self::assertSame(
            2,
            $version->getAttribute('lock_version'),
        );

        self::assertSame(
            1,
            $version->getAttribute('version_number'),
        );

        self::assertSame(
            1,
            $priceList->getAttribute('current_version'),
        );

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $version->getKey(),
            'version_number' => 1,
            'lock_version' => 2,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-08-01 00:00:00',
            'valid_until' => '2026-12-31 00:00:00',
            'change_reason' => 'Updated draft rates',
        ]);

        $this->assertDatabaseCount('price_list_items', 4);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'description' => 'Delivered parcel',
            'calculation_method' => (
                PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
            ),
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '12.5000',
            'currency' => 'CZK',
            'quantity_source' => (
                PriceListItem::CODE_DELIVERED_PARCELS
            ),
            'rounding_scale' => 2,
            'rounding_method' => (
                PriceListItem::ROUNDING_METHOD_HALF_UP
            ),
            'position' => 1,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '8.0000',
            'currency' => 'CZK',
            'quantity_source' => (
                PriceListItem::CODE_REDIRECTED_PARCELS
            ),
            'position' => 2,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '3.0000',
            'currency' => 'CZK',
            'quantity_source' => (
                PriceListItem::CODE_UNDELIVERED_PARCELS
            ),
            'position' => 3,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'unit' => PriceListItem::UNIT_KM,
            'unit_rate' => '5.2500',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::CODE_ACTUAL_KM,
            'position' => 4,
        ]);

        $this->assertDatabaseMissing('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'unit_rate' => '1.0000',
        ]);
    }

    public function test_stale_lock_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Stale update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $this->updatePayload(),
            )
            ->assertOk();

        $stalePayload = $this->updatePayload(1);

        $stalePayload['items'][0]['unit_rate'] = '99.0000';
        $stalePayload['change_reason'] = 'Stale overwrite';

        $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $stalePayload,
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price-list draft version has changed.',
            );

        $version->refresh();

        self::assertSame(
            2,
            $version->getAttribute('lock_version'),
        );

        self::assertSame(
            'Updated draft rates',
            $version->getAttribute('change_reason'),
        );

        $this->assertDatabaseCount('price_list_items', 4);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'unit_rate' => '5.2500',
        ]);

        $this->assertDatabaseMissing('price_list_items', [
            'price_list_version_id' => $version->getKey(),
            'unit_rate' => '99.0000',
        ]);
    }

    public function test_non_draft_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Approved update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $version->update([
            'status' => PriceListVersion::STATUS_APPROVED,
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => now(),
        ]);

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $this->updatePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only draft price-list versions may be updated.',
            );

        $version->refresh();

        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $version->getAttribute('status'),
        );

        self::assertSame(
            1,
            $version->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    public function test_version_update_is_owner_organization_scoped(): void
    {
        [$user, $authorizedCustomer] = $this->createContext();

        $foreignCustomer = $this->createOrganization(
            'Foreign update customer',
            Organization::TYPE_CARRIER,
        );

        $foreignProvider = $this->createOrganization(
            'Foreign update provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
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
            ->putJson(
                $this->updateUrl($priceList, $version),
                $this->updatePayload(),
            )
            ->assertNotFound();

        self::assertSame(
            1,
            $version->refresh()->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    public function test_complete_pricing_item_set_is_required(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Incomplete-set provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createDraftAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $payload = $this->updatePayload();

        $payload['items'] = array_slice(
            $payload['items'],
            0,
            3,
        );

        $this->withOrganization($customer)
            ->putJson(
                $this->updateUrl($priceList, $version),
                $payload,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items',
            ]);

        self::assertSame(
            1,
            $version->refresh()->getAttribute('lock_version'),
        );

        $this->assertDatabaseCount('price_list_items', 0);
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(): array
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Price-list version write context',
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
            'name' => 'Draft version write test',
            'description' => null,
            'currency' => 'CZK',
            'status' => PriceList::STATUS_DRAFT,
            'current_version' => 1,
            'created_by_user_id' => $creator->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => null,
            'valid_until' => null,
            'change_reason' => null,
            'created_by_user_id' => $creator->getKey(),
        ]);

        return [
            $priceList,
            $version,
        ];
    }

    private function seedOldItem(
        PriceListVersion $version,
    ): void {
        $version->items()->create([
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'description' => 'Old delivered rate',
            'calculation_method' => (
                PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
            ),
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '1.0000',
            'currency' => 'CZK',
            'quantity_source' => (
                PriceListItem::CODE_DELIVERED_PARCELS
            ),
            'rounding_scale' => 2,
            'rounding_method' => (
                PriceListItem::ROUNDING_METHOD_HALF_UP
            ),
            'position' => 1,
        ]);
    }

    private function updateUrl(
        PriceList $priceList,
        PriceListVersion $version,
    ): string {
        return sprintf(
            '/api/v1/price-lists/%s/versions/%d',
            (string) $priceList->getAttribute('public_id'),
            (int) $version->getAttribute('version_number'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(
        int $expectedLockVersion = 1,
    ): array {
        return [
            'expected_lock_version' => $expectedLockVersion,
            'valid_from' => '2026-08-01',
            'valid_until' => '2026-12-31',
            'change_reason' => '  Updated draft rates  ',
            'items' => [
                [
                    'code' => PriceListItem::CODE_ACTUAL_KM,
                    'description' => '  Actual kilometre  ',
                    'unit_rate' => '5.2500',
                ],
                [
                    'code' => (
                        PriceListItem::CODE_UNDELIVERED_PARCELS
                    ),
                    'description' => '  Undelivered parcel  ',
                    'unit_rate' => '3.0000',
                ],
                [
                    'code' => (
                        PriceListItem::CODE_DELIVERED_PARCELS
                    ),
                    'description' => '  Delivered parcel  ',
                    'unit_rate' => '12.5000',
                ],
                [
                    'code' => (
                        PriceListItem::CODE_REDIRECTED_PARCELS
                    ),
                    'description' => '  Redirected parcel  ',
                    'unit_rate' => '8.0000',
                ],
            ],
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
