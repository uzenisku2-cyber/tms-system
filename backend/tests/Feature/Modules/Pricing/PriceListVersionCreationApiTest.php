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

final class PriceListVersionCreationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_create_price_list_version(): void
    {
        [$creator, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Guest version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $creator,
            $customer,
            $provider,
        );

        $this->postJson(
            $this->storeUrl($priceList),
            $this->storePayload(),
        )->assertUnauthorized();

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_organization_context_is_required_for_version_creation(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->postJson(
            $this->storeUrl($priceList),
            $this->storePayload(),
        )->assertStatus(400);

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_pricing_manage_permission_is_required_for_version_creation(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(),
            )
            ->assertForbidden();

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_it_creates_empty_next_draft_version_and_advances_current_version(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Successful version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $currentVersion] =
            $this->createVersionableAggregate(
                $user,
                $customer,
                $provider,
            );

        $this->seedSourceItem($currentVersion);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(),
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Price list draft version created.',
            )
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 1)
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_DRAFT,
            )
            ->assertJsonPath(
                'data.valid_from',
                '2026-09-01',
            )
            ->assertJsonPath(
                'data.valid_until',
                '2026-12-31',
            )
            ->assertJsonPath(
                'data.change_reason',
                'New contractual rates',
            )
            ->assertJsonPath('data.approved_at', null)
            ->assertJsonPath('data.activated_at', null)
            ->assertJsonCount(0, 'data.items');

        $responseData = $response->json('data');

        self::assertIsArray($responseData);
        self::assertArrayNotHasKey('id', $responseData);
        self::assertArrayNotHasKey(
            'price_list_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'created_by_user_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'approved_by_user_id',
            $responseData,
        );

        $priceList->refresh();
        $currentVersion->refresh();

        self::assertSame(
            2,
            $priceList->getAttribute('current_version'),
        );

        self::assertSame(
            PriceList::STATUS_ACTIVE,
            $priceList->getAttribute('status'),
        );

        self::assertSame(
            PriceListVersion::STATUS_ACTIVE,
            $currentVersion->getAttribute('status'),
        );

        $newVersion = PriceListVersion::query()
            ->where(
                'price_list_id',
                $priceList->getKey(),
            )
            ->where('version_number', 2)
            ->sole();

        self::assertSame(
            0,
            $newVersion->items()->count(),
        );

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $newVersion->getKey(),
            'price_list_id' => $priceList->getKey(),
            'version_number' => 2,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-09-01 00:00:00',
            'valid_until' => '2026-12-31 00:00:00',
            'change_reason' => 'New contractual rates',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'activated_at' => null,
        ]);

        $this->assertDatabaseHas('price_list_items', [
            'price_list_version_id' => $currentVersion->getKey(),
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'unit_rate' => '11.0000',
        ]);

        $this->assertDatabaseMissing('price_list_items', [
            'price_list_version_id' => $newVersion->getKey(),
        ]);

        $this->assertDatabaseCount(
            'price_list_versions',
            2,
        );

        $this->assertDatabaseCount(
            'price_list_items',
            1,
        );
    }

    public function test_stale_current_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Stale version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(2),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price list has changed.',
            );

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_existing_current_draft_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Existing-draft version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_DRAFT,
            PriceListVersion::STATUS_DRAFT,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'A draft price-list version already exists.',
            );

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_archived_price_list_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Archived version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
            PriceList::STATUS_ARCHIVED,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                (
                    'Archived price lists cannot receive '.
                    'new versions.'
                ),
            );

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_provider_organization_cannot_create_owner_draft(): void
    {
        [$user, $provider] = $this->createContext(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $customer = $this->createOrganization(
            'Owner-scope version customer',
            Organization::TYPE_CARRIER,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission($user, $provider);

        Sanctum::actingAs($user);

        $this->withOrganization($provider)
            ->postJson(
                $this->storeUrl($priceList),
                $this->storePayload(),
            )
            ->assertNotFound();

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    public function test_request_validates_concurrency_and_effective_period(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Validation version provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList] = $this->createVersionableAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->storeUrl($priceList),
                [
                    'expected_current_version' => 0,
                    'valid_from' => '2026-12-31',
                    'valid_until' => '2026-01-01',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'expected_current_version',
                'valid_until',
            ]);

        self::assertSame(
            1,
            $priceList->refresh()->getAttribute('current_version'),
        );

        $this->assertDatabaseCount('price_list_versions', 1);
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(
        string $organizationType = Organization::TYPE_CARRIER,
    ): array {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Price-list version creation context',
            $organizationType,
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
    private function createVersionableAggregate(
        User $creator,
        Organization $customer,
        Organization $provider,
        string $priceListStatus = PriceList::STATUS_ACTIVE,
        string $versionStatus = PriceListVersion::STATUS_ACTIVE,
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
            'name' => 'Version creation test',
            'description' => null,
            'currency' => 'CZK',
            'status' => $priceListStatus,
            'current_version' => 1,
            'created_by_user_id' => $creator->getKey(),
        ]);

        $isDraft = (
            $versionStatus === PriceListVersion::STATUS_DRAFT
        );

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => $versionStatus,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'change_reason' => 'Initial contractual rates',
            'created_by_user_id' => $creator->getKey(),
            'approved_by_user_id' => $isDraft
                ? null
                : $creator->getKey(),
            'approved_at' => $isDraft
                ? null
                : now()->subDay(),
            'activated_at' => (
                $versionStatus ===
                PriceListVersion::STATUS_ACTIVE
            )
                ? now()->subDay()
                : null,
        ]);

        return [
            $priceList,
            $version,
        ];
    }

    private function seedSourceItem(
        PriceListVersion $version,
    ): void {
        $version->items()->create([
            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
            'description' => 'Existing active delivered rate',
            'calculation_method' => (
                PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE
            ),
            'unit' => PriceListItem::UNIT_PARCEL,
            'unit_rate' => '11.0000',
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

    private function storeUrl(
        PriceList $priceList,
    ): string {
        return sprintf(
            '/api/v1/price-lists/%s/versions',
            (string) $priceList->getAttribute('public_id'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function storePayload(
        int $expectedCurrentVersion = 1,
    ): array {
        return [
            'expected_current_version' => (
                $expectedCurrentVersion
            ),
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-12-31',
            'change_reason' => '  New contractual rates  ',
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
