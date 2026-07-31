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

final class PriceListReadApiTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_URL = '/api/v1/price-lists';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_access_price_lists(): void
    {
        $this->getJson(self::INDEX_URL)
            ->assertUnauthorized();
    }

    public function test_missing_organization_context_is_rejected(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        Sanctum::actingAs($user);

        $this->getJson(self::INDEX_URL)
            ->assertStatus(400);
    }

    public function test_pricing_view_permission_is_required(): void
    {
        [$user, $organization] = $this->createContext();

        Sanctum::actingAs($user);

        $this->withOrganization($organization)
            ->getJson(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_index_is_filtered_and_relationship_scoped(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $provider = $this->createOrganization(
            'Visible provider',
        );

        $activeRelationship = $this->createRelationship(
            $organization,
            $provider,
        );

        $visible = $this->createPriceList(
            relationship: $activeRelationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'Visible active price list',
            status: PriceList::STATUS_ACTIVE,
        );

        $draft = $this->createPriceList(
            relationship: $activeRelationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'Visible draft price list',
            status: PriceList::STATUS_DRAFT,
        );

        $endedProvider = $this->createOrganization(
            'Ended provider',
        );

        $endedRelationship = $this->createRelationship(
            $organization,
            $endedProvider,
            OrganizationRelationship::STATUS_ENDED,
        );

        $ended = $this->createPriceList(
            relationship: $endedRelationship,
            customer: $organization,
            provider: $endedProvider,
            creator: $user,
            name: 'Ended relationship price list',
            status: PriceList::STATUS_ACTIVE,
        );

        $foreignCustomer = $this->createOrganization(
            'Foreign customer',
        );

        $foreignProvider = $this->createOrganization(
            'Foreign provider',
        );

        $foreignRelationship = $this->createRelationship(
            $foreignCustomer,
            $foreignProvider,
        );

        $foreign = $this->createPriceList(
            relationship: $foreignRelationship,
            customer: $foreignCustomer,
            provider: $foreignProvider,
            creator: $user,
            name: 'Foreign price list',
            status: PriceList::STATUS_ACTIVE,
        );

        Sanctum::actingAs($user);

        $response = $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.
            '?status=active'.
            '&currency=czk'.
            '&sort_by=name'.
            '&sort_dir=asc'.
            '&per_page=10',
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.pagination.total',
                1,
            )
            ->assertJsonFragment([
                'public_id' => $visible->getRouteKey(),
            ])
            ->assertJsonMissing([
                'public_id' => $draft->getRouteKey(),
            ])
            ->assertJsonMissing([
                'public_id' => $ended->getRouteKey(),
            ])
            ->assertJsonMissing([
                'public_id' => $foreign->getRouteKey(),
            ]);
    }

    public function test_show_hides_unrelated_price_list_and_internal_ids(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $provider = $this->createOrganization(
            'Visible provider',
        );

        $relationship = $this->createRelationship(
            $organization,
            $provider,
        );

        $visible = $this->createPriceList(
            relationship: $relationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'Visible price list',
        );

        $foreignCustomer = $this->createOrganization(
            'Foreign customer',
        );

        $foreignProvider = $this->createOrganization(
            'Foreign provider',
        );

        $foreignRelationship = $this->createRelationship(
            $foreignCustomer,
            $foreignProvider,
        );

        $foreign = $this->createPriceList(
            relationship: $foreignRelationship,
            customer: $foreignCustomer,
            provider: $foreignProvider,
            creator: $user,
            name: 'Foreign price list',
        );

        Sanctum::actingAs($user);

        $response = $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.'/'.$visible->getRouteKey(),
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.public_id',
                $visible->getRouteKey(),
            )
            ->assertJsonPath(
                'data.name',
                'Visible price list',
            );

        $data = $response->json('data');

        self::assertIsArray($data);
        self::assertArrayNotHasKey('id', $data);
        self::assertArrayNotHasKey(
            'organization_relationship_id',
            $data,
        );
        self::assertArrayNotHasKey(
            'owner_organization_id',
            $data,
        );
        self::assertArrayNotHasKey(
            'customer_organization_id',
            $data,
        );
        self::assertArrayNotHasKey(
            'provider_organization_id',
            $data,
        );
        self::assertArrayNotHasKey(
            'created_by_user_id',
            $data,
        );

        $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.'/'.$foreign->getRouteKey(),
        )->assertNotFound();
    }

    public function test_versions_are_nested_ordered_and_include_items(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $provider = $this->createOrganization(
            'Version provider',
        );

        $relationship = $this->createRelationship(
            $organization,
            $provider,
        );

        $priceList = $this->createPriceList(
            relationship: $relationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'Versioned price list',
            status: PriceList::STATUS_ACTIVE,
            currentVersion: 2,
        );

        $this->createVersion(
            priceList: $priceList,
            creator: $user,
            versionNumber: 1,
            status: PriceListVersion::STATUS_DRAFT,
        );

        $activeVersion = $this->createVersion(
            priceList: $priceList,
            creator: $user,
            versionNumber: 2,
            status: PriceListVersion::STATUS_ACTIVE,
        );

        PriceListItem::query()->create([
            'price_list_version_id' => $activeVersion->getKey(),
            'code' => PriceListItem::CODE_ACTUAL_KM,
            'description' => 'Actual kilometre rate',
            'calculation_method' => PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
            'unit' => PriceListItem::UNIT_KM,
            'unit_rate' => '2.5000',
            'currency' => 'CZK',
            'quantity_source' => PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            'rounding_scale' => 2,
            'rounding_method' => PriceListItem::ROUNDING_METHOD_HALF_UP,
            'position' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.
            '/'.
            $priceList->getRouteKey().
            '/versions',
        )
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath(
                'data.items.0.version_number',
                2,
            )
            ->assertJsonPath(
                'data.items.0.items.0.code',
                PriceListItem::CODE_ACTUAL_KM,
            )
            ->assertJsonPath(
                'data.items.0.items.0.unit_rate',
                '2.5000',
            )
            ->assertJsonPath(
                'data.items.1.version_number',
                1,
            );
    }

    public function test_version_lookup_cannot_escape_parent_price_list(): void
    {
        [$user, $organization] = $this->createContext();

        $this->grantViewPermission(
            $user,
            $organization,
        );

        $provider = $this->createOrganization(
            'Nested provider',
        );

        $relationship = $this->createRelationship(
            $organization,
            $provider,
        );

        $firstPriceList = $this->createPriceList(
            relationship: $relationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'First price list',
        );

        $secondPriceList = $this->createPriceList(
            relationship: $relationship,
            customer: $organization,
            provider: $provider,
            creator: $user,
            name: 'Second price list',
            currentVersion: 2,
        );

        $this->createVersion(
            priceList: $firstPriceList,
            creator: $user,
            versionNumber: 1,
        );

        $this->createVersion(
            priceList: $secondPriceList,
            creator: $user,
            versionNumber: 2,
        );

        Sanctum::actingAs($user);

        $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.
            '/'.
            $firstPriceList->getRouteKey().
            '/versions/1',
        )
            ->assertOk()
            ->assertJsonPath(
                'data.version_number',
                1,
            );

        $this->withOrganization(
            $organization,
        )->getJson(
            self::INDEX_URL.
            '/'.
            $firstPriceList->getRouteKey().
            '/versions/2',
        )->assertNotFound();
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(): array
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Pricing test organization',
        );

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
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
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function createRelationship(
        Organization $customer,
        Organization $provider,
        string $status = OrganizationRelationship::STATUS_ACTIVE,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->getKey(),
            'target_organization_id' => $provider->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => $status,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);
    }

    private function createPriceList(
        OrganizationRelationship $relationship,
        Organization $customer,
        Organization $provider,
        User $creator,
        string $name,
        string $status = PriceList::STATUS_DRAFT,
        int $currentVersion = 1,
    ): PriceList {
        return PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => $name,
            'description' => null,
            'currency' => 'CZK',
            'status' => $status,
            'current_version' => $currentVersion,
            'created_by_user_id' => $creator->getKey(),
        ]);
    }

    private function createVersion(
        PriceList $priceList,
        User $creator,
        int $versionNumber,
        string $status = PriceListVersion::STATUS_DRAFT,
    ): PriceListVersion {
        $official =
            $status !== PriceListVersion::STATUS_DRAFT;

        return PriceListVersion::query()->create([
            'price_list_id' => $priceList->getKey(),
            'version_number' => $versionNumber,
            'status' => $status,
            'valid_from' => $official
                ? now()->toDateString()
                : null,
            'valid_until' => null,
            'change_reason' => null,
            'created_by_user_id' => $creator->getKey(),
            'approved_by_user_id' => $official
                ? $creator->getKey()
                : null,
            'approved_at' => $official
                ? now()
                : null,
            'activated_at' => $status === PriceListVersion::STATUS_ACTIVE
                    ? now()
                    : null,
        ]);
    }

    private function grantViewPermission(
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
                'pricing.view',
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
