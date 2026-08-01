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
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PriceListVersionExpirationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $now = CarbonImmutable::parse(
            '2026-08-15 12:00:00',
            'Europe/Prague',
        );

        Carbon::setTestNow($now);
        CarbonImmutable::setTestNow($now);
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_expire_price_list_version(): void
    {
        [$creator, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Guest expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $creator,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        $this->postJson(
            $this->expireUrl($priceList, $version),
            $this->expirePayload(),
        )->assertUnauthorized();

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_organization_context_is_required_for_version_expiration(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Missing-context expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->postJson(
            $this->expireUrl($priceList, $version),
            $this->expirePayload(),
        )->assertStatus(400);

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_pricing_manage_permission_is_required_for_version_expiration(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Permission expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(),
            )
            ->assertForbidden();

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_it_expires_current_active_version_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Successful expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
            lockVersion: 3,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        $approvedAt = $version->getAttribute('approved_at');
        $activatedAt = $version->getAttribute('activated_at');

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    expectedLockVersion: 3,
                    validUntil: '2026-08-10',
                ),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Price list version expired.',
            )
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.lock_version', 3)
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_EXPIRED,
            )
            ->assertJsonPath(
                'data.valid_from',
                '2026-07-01',
            )
            ->assertJsonPath(
                'data.valid_until',
                '2026-08-10',
            )
            ->assertJsonPath(
                'data.change_reason',
                'Expiration candidate',
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

        self::assertTrue($priceList->isActive());
        self::assertSame(
            1,
            $priceList->getAttribute('current_version'),
        );

        self::assertTrue($version->isExpired());
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
        self::assertEquals(
            $activatedAt,
            $version->getAttribute('activated_at'),
        );

        $validUntil = $version->getAttribute('valid_until');

        self::assertInstanceOf(
            DateTimeInterface::class,
            $validUntil,
        );
        self::assertSame(
            '2026-08-10',
            $validUntil->format('Y-m-d'),
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
            'status' => PriceListVersion::STATUS_EXPIRED,
            'approved_by_user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseCount('price_list_items', 4);
    }

    public function test_stale_lock_version_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Stale expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
            lockVersion: 2,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    expectedLockVersion: 1,
                ),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price-list active version has changed.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
            expectedLockVersion: 2,
        );
    }

    public function test_non_current_active_version_may_be_expired_while_current_version_is_preserved(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Non-current expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
            currentVersion: 2,
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
            'approved_at' => '2026-08-14 10:00:00',
            'activated_at' => null,
        ]);

        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $response = $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    validUntil: '2026-08-10',
                ),
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Price list version expired.',
            )
            ->assertJsonPath(
                'data.status',
                PriceListVersion::STATUS_EXPIRED,
            )
            ->assertJsonPath(
                'data.version_number',
                1,
            )
            ->assertJsonPath(
                'data.valid_until',
                '2026-08-10',
            );

        $priceList->refresh();
        $version->refresh();
        $current->refresh();

        self::assertTrue($priceList->isActive());
        self::assertSame(
            2,
            $priceList->getAttribute('current_version'),
        );

        self::assertTrue($version->isExpired());

        $validUntil = $version->getAttribute('valid_until');

        self::assertInstanceOf(
            DateTimeInterface::class,
            $validUntil,
        );

        self::assertSame(
            '2026-08-10',
            $validUntil->format('Y-m-d'),
        );

        self::assertTrue($current->isApproved());
        self::assertNull(
            $current->getAttribute('activated_at'),
        );

        $this->assertDatabaseHas('price_lists', [
            'id' => $priceList->getKey(),
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 2,
        ]);

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $version->getKey(),
            'version_number' => 1,
            'status' => PriceListVersion::STATUS_EXPIRED,
        ]);

        $this->assertDatabaseHas('price_list_versions', [
            'id' => $current->getKey(),
            'version_number' => 2,
            'status' => PriceListVersion::STATUS_APPROVED,
            'activated_at' => null,
        ]);

        $this->assertDatabaseCount(
            'price_list_versions',
            2,
        );

        $this->assertDatabaseCount(
            'price_list_items',
            4,
        );
    }

    public function test_only_active_version_may_be_expired(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Replaced expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $version->update([
            'status' => PriceListVersion::STATUS_REPLACED,
            'valid_until' => '2026-08-05',
        ]);

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only active price-list versions may be expired.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
            expectedVersionStatus: PriceListVersion::STATUS_REPLACED,
            expectedValidUntil: '2026-08-05',
        );
    }

    public function test_expiration_date_cannot_precede_active_start(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Early expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    validUntil: '2026-06-30',
                ),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The expiration date cannot precede the active version start date.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_expiration_date_cannot_be_in_the_future(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Future expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    validUntil: '2026-08-16',
                ),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The expiration date cannot be in the future.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_expiration_cannot_extend_existing_effective_period(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Period-extension expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
            validUntil: '2026-08-05',
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(
                    validUntil: '2026-08-10',
                ),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The expiration date cannot extend the active version effective period.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
            expectedValidUntil: '2026-08-05',
        );
    }

    public function test_multiple_active_versions_require_manual_repair(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Broken expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $otherActiveVersion = $priceList->versions()->create([
            'version_number' => 2,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => 'Unexpected second active version',
            'created_by_user_id' => $user->getKey(),
            'approved_by_user_id' => $user->getKey(),
            'approved_at' => '2026-07-31 10:00:00',
            'activated_at' => '2026-08-01 00:00:00',
        ]);

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The price-list aggregate contains multiple active versions and requires repair.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );

        self::assertTrue(
            $otherActiveVersion->refresh()->isActive(),
        );

        $this->assertDatabaseCount(
            'price_list_versions',
            2,
        );
    }

    public function test_archived_price_list_is_rejected_atomically(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Archived expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
            priceListStatus: PriceList::STATUS_ARCHIVED,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                $this->expirePayload(),
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Archived price lists cannot expire versions.',
            );

        $this->assertAggregatePreserved(
            $priceList,
            $version,
            expectedPriceListStatus: PriceList::STATUS_ARCHIVED,
        );
    }

    public function test_version_expiration_is_owner_organization_scoped(): void
    {
        [$user, $authorizedCustomer] = $this->createContext();

        $foreignCustomer = $this->createOrganization(
            'Foreign expiration customer',
            Organization::TYPE_CARRIER,
        );

        $foreignProvider = $this->createOrganization(
            'Foreign expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
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
                $this->expireUrl($priceList, $version),
                $this->expirePayload(),
            )
            ->assertNotFound();

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    public function test_request_validates_lock_and_expiration_date(): void
    {
        [$user, $customer] = $this->createContext();

        $provider = $this->createOrganization(
            'Validation expiration provider',
            Organization::TYPE_SUBCONTRACTOR,
        );

        [$priceList, $version] = $this->createActiveAggregate(
            $user,
            $customer,
            $provider,
        );

        $this->seedCanonicalItems($version);
        $this->grantManagePermission($user, $customer);

        Sanctum::actingAs($user);

        $this->withOrganization($customer)
            ->postJson(
                $this->expireUrl($priceList, $version),
                [
                    'expected_lock_version' => 0,
                    'valid_until' => 'not-a-date',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'expected_lock_version',
                'valid_until',
            ]);

        $this->assertAggregatePreserved(
            $priceList,
            $version,
        );
    }

    /**
     * @return array{User, Organization}
     */
    private function createContext(): array
    {
        $user = User::factory()->create();

        $organization = $this->createOrganization(
            'Price-list version expiration context',
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
            'valid_from' => now()->subMonth(),
            'valid_until' => null,
        ]);
    }

    /**
     * @return array{PriceList, PriceListVersion}
     */
    private function createActiveAggregate(
        User $creator,
        Organization $customer,
        Organization $provider,
        string $priceListStatus = PriceList::STATUS_ACTIVE,
        int $currentVersion = 1,
        int $versionNumber = 1,
        int $lockVersion = 1,
        ?string $validFrom = '2026-07-01',
        ?string $validUntil = null,
        string $versionStatus = PriceListVersion::STATUS_ACTIVE,
        ?string $activatedAt = '2026-07-01 00:00:00',
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
            'name' => 'Version expiration test',
            'description' => null,
            'currency' => 'CZK',
            'status' => $priceListStatus,
            'current_version' => $currentVersion,
            'created_by_user_id' => $creator->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => $versionNumber,
            'lock_version' => $lockVersion,
            'status' => $versionStatus,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'change_reason' => 'Expiration candidate',
            'created_by_user_id' => $creator->getKey(),
            'approved_by_user_id' => $creator->getKey(),
            'approved_at' => '2026-06-30 10:00:00',
            'activated_at' => $activatedAt,
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

    private function assertAggregatePreserved(
        PriceList $priceList,
        PriceListVersion $version,
        int $expectedLockVersion = 1,
        int $expectedCurrentVersion = 1,
        string $expectedPriceListStatus = PriceList::STATUS_ACTIVE,
        string $expectedVersionStatus = PriceListVersion::STATUS_ACTIVE,
        ?string $expectedValidUntil = null,
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
            $expectedVersionStatus,
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
        self::assertInstanceOf(
            DateTimeInterface::class,
            $version->getAttribute('activated_at'),
        );

        $actualValidUntil = $version->getAttribute('valid_until');

        if ($expectedValidUntil === null) {
            self::assertNull($actualValidUntil);
        } else {
            self::assertInstanceOf(
                DateTimeInterface::class,
                $actualValidUntil,
            );
            self::assertSame(
                $expectedValidUntil,
                $actualValidUntil->format('Y-m-d'),
            );
        }

        $this->assertDatabaseCount(
            'price_list_items',
            4,
        );
    }

    private function expireUrl(
        PriceList $priceList,
        PriceListVersion $version,
    ): string {
        return sprintf(
            '/api/v1/price-lists/%s/versions/%d/expire',
            (string) $priceList->getAttribute('public_id'),
            (int) $version->getAttribute('version_number'),
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function expirePayload(
        int $expectedLockVersion = 1,
        string $validUntil = '2026-08-10',
    ): array {
        return [
            'expected_lock_version' => $expectedLockVersion,
            'valid_until' => $validUntil,
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
