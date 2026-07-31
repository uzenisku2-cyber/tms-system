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
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PriceListWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_URL = '/api/v1/price-lists';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_guest_cannot_create_price_list(): void
    {
        $this->postJson(
            self::STORE_URL,
            [],
        )->assertUnauthorized();
    }

    public function test_organization_context_is_required(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->postJson(
            self::STORE_URL,
            $this->payload($relationship),
        )->assertStatus(400);
    }

    public function test_pricing_manage_permission_is_required(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                self::STORE_URL,
                $this->payload($relationship),
            )
            ->assertForbidden();

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    public function test_pricing_manage_permission_is_organization_scoped(): void
    {
        [$user, $authorizedCustomer] = $this->createContext();

        $foreignCustomer = $this->createOrganization(
            'Foreign pricing customer',
            Organization::TYPE_CARRIER,
        );

        OrganizationMembership::query()->create([
            'organization_id' => $foreignCustomer->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $foreignProvider = $this->createOrganization(
            'Foreign pricing provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $foreignRelationship = $this->createRelationship(
            $foreignCustomer,
            $foreignProvider,
        );

        $this->grantManagePermission(
            $user,
            $authorizedCustomer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($foreignCustomer)
            ->postJson(
                self::STORE_URL,
                $this->payload($foreignRelationship),
            )
            ->assertForbidden();

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    public function test_it_creates_draft_and_initial_version_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Creation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $response = $this->withOrganization(
            $customer,
        )->postJson(
            self::STORE_URL,
            [
                'organization_relationship_id' => $relationship->getKey(),

                'name' => '  Standard partner pricing  ',

                'description' => '  Initial pricing agreement  ',

                'currency' => 'czk',

                'valid_from' => '2026-08-01',

                'valid_until' => null,

                'change_reason' => '  Initial draft  ',
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Price list draft created.',
            )
            ->assertJsonPath(
                'data.name',
                'Standard partner pricing',
            )
            ->assertJsonPath(
                'data.description',
                'Initial pricing agreement',
            )
            ->assertJsonPath(
                'data.currency',
                'CZK',
            )
            ->assertJsonPath(
                'data.status',
                PriceList::STATUS_DRAFT,
            )
            ->assertJsonPath(
                'data.current_version',
                1,
            );

        $publicId = $response->json(
            'data.public_id',
        );

        self::assertIsString($publicId);
        self::assertTrue(Str::isUuid($publicId));

        $responseData = $response->json('data');

        self::assertIsArray($responseData);
        self::assertArrayNotHasKey('id', $responseData);
        self::assertArrayNotHasKey(
            'organization_relationship_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'owner_organization_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'customer_organization_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'provider_organization_id',
            $responseData,
        );
        self::assertArrayNotHasKey(
            'created_by_user_id',
            $responseData,
        );

        $priceList = PriceList::query()
            ->where('public_id', $publicId)
            ->sole();

        $this->assertDatabaseHas('price_lists', [
            'id' => $priceList->getKey(),
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Standard partner pricing',
            'description' => 'Initial pricing agreement',
            'currency' => 'CZK',
            'status' => PriceList::STATUS_DRAFT,
            'current_version' => 1,
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas('price_list_versions', [
            'price_list_id' => $priceList->getKey(),
            'version_number' => 1,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-08-01 00:00:00',
            'valid_until' => null,
            'change_reason' => 'Initial draft',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'activated_at' => null,
        ]);

        $this->assertDatabaseCount(
            'price_list_versions',
            1,
        );

        self::assertSame(
            0,
            PriceListItem::query()->count(),
        );
    }

    public function test_provider_cannot_reverse_relationship_direction(): void
    {
        [$user, $provider] = $this->createContext(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $customer = $this->createOrganization(
            'Direction customer',
            Organization::TYPE_CARRIER,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $provider,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($provider)
            ->postJson(
                self::STORE_URL,
                $this->payload($relationship),
            )
            ->assertNotFound();

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    public function test_inactive_relationship_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Inactive provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
            OrganizationRelationship::STATUS_ENDED,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                self::STORE_URL,
                $this->payload($relationship),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'organization_relationship_id',
            ]);

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    public function test_invalid_effective_period_is_rejected(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Validation provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->createRelationship(
            $customer,
            $provider,
        );

        $this->grantManagePermission(
            $user,
            $customer,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                self::STORE_URL,
                [
                    'organization_relationship_id' => $relationship->getKey(),

                    'name' => 'Invalid draft',

                    'currency' => 'CZ',

                    'valid_from' => '2026-08-10',

                    'valid_until' => '2026-08-01',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'currency',
                'valid_until',
            ]);

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(
        string $organizationType = Organization::TYPE_CARRIER,
    ): array {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Pricing write context',
            $organizationType,
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

    /**
     * @return array<string, mixed>
     */
    private function payload(
        OrganizationRelationship $relationship,
    ): array {
        return [
            'organization_relationship_id' => $relationship->getKey(),

            'name' => 'Partner price list',

            'description' => null,

            'currency' => 'CZK',

            'valid_from' => null,

            'valid_until' => null,

            'change_reason' => null,
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
