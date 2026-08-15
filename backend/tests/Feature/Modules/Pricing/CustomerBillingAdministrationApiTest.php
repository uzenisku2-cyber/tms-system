<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CustomerBillingAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_provider_lists_and_reads_only_its_customer_relationships(): void
    {
        $foundation = $this->foundation();

        $foreignProvider = $this->organization('Foreign Provider');
        $foreignCustomer = $this->organization('Foreign Customer');

        OrganizationRelationship::query()->create([
            'source_organization_id' => $foreignCustomer->getKey(),
            'target_organization_id' => $foreignProvider->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => null,
        ]);

        $endedCustomer = $this->organization('Ended Customer');

        OrganizationRelationship::query()->create([
            'source_organization_id' => $endedCustomer->getKey(),
            'target_organization_id' => $foundation['provider']->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ENDED,
            'valid_from' => now()->subDays(10)->toDateString(),
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        $futureCustomer = $this->organization('Future Customer');

        OrganizationRelationship::query()->create([
            'source_organization_id' => $futureCustomer->getKey(),
            'target_organization_id' => $foundation['provider']->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->addDay()->toDateString(),
            'valid_until' => null,
        ]);

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $response = $this
            ->getJson('/api/v1/customers');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.relationship_id',
                $foundation['relationship']->getKey(),
            )
            ->assertJsonPath(
                'data.0.customer.id',
                $foundation['customer']->getKey(),
            )
            ->assertJsonPath(
                'data.0.customer.name',
                'Customer',
            );

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $this
            ->getJson(
                '/api/v1/customers/'.
                $foundation['relationship']->getKey(),
            )
            ->assertOk()
            ->assertJsonPath(
                'data.customer.id',
                $foundation['customer']->getKey(),
            );
    }

    public function test_provider_creates_billing_draft_without_reversing_financial_direction(): void
    {
        $foundation = $this->foundation();

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $response = $this
            ->postJson(
                '/api/v1/customers/'.
                $foundation['relationship']->getKey().
                '/price-lists',
                [
                    'name' => 'Billing 2026',
                    'currency' => 'CZK',
                    'valid_from' => '2026-01-01',
                    'valid_until' => '2026-12-31',
                    'change_reason' => 'Initial customer billing tariff.',
                    'items' => [
                        [
                            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
                            'description' => 'Doručená zásilka',
                            'unit_rate' => '12.5000',
                        ],
                        [
                            'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                            'description' => 'Přesměrovaná zásilka',
                            'unit_rate' => '8.0000',
                        ],
                        [
                            'code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
                            'description' => 'Nedoručená zásilka',
                            'unit_rate' => '3.0000',
                        ],
                        [
                            'code' => PriceListItem::CODE_ACTUAL_KM,
                            'description' => 'Skutečný kilometr',
                            'unit_rate' => '5.2500',
                        ],
                    ],
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Billing 2026',
            )
            ->assertJsonPath(
                'data.currency',
                'CZK',
            );

        $priceList = PriceList::query()->sole();

        self::assertSame(
            $foundation['relationship']->getKey(),
            $priceList->getAttribute('organization_relationship_id'),
        );

        self::assertSame(
            $foundation['customer']->getKey(),
            $priceList->getAttribute('owner_organization_id'),
        );

        self::assertSame(
            $foundation['customer']->getKey(),
            $priceList->getAttribute('customer_organization_id'),
        );

        self::assertSame(
            $foundation['provider']->getKey(),
            $priceList->getAttribute('provider_organization_id'),
        );

        self::assertSame(
            $foundation['provider']->getKey(),
            $priceList->getAttribute('managed_by_organization_id'),
        );

        $version = $priceList->versions()->sole();

        self::assertSame(
            1,
            $version->getAttribute('version_number'),
        );

        self::assertSame(
            'draft',
            $version->getAttribute('status'),
        );

        self::assertSame(
            '2026-01-01',
            $version->getAttribute('valid_from')?->format('Y-m-d'),
        );

        self::assertSame(
            '2026-12-31',
            $version->getAttribute('valid_until')?->format('Y-m-d'),
        );
        $items =
            $version->items()
                ->orderBy('position')
                ->get();

        self::assertCount(4, $items);

        self::assertSame(
            [
                PriceListItem::CODE_DELIVERED_PARCELS,
                PriceListItem::CODE_REDIRECTED_PARCELS,
                PriceListItem::CODE_UNDELIVERED_PARCELS,
                PriceListItem::CODE_ACTUAL_KM,
            ],
            $items->pluck('code')->all(),
        );

        self::assertSame(
            [
                '12.5000',
                '8.0000',
                '3.0000',
                '5.2500',
            ],
            $items->pluck('unit_rate')->all(),
        );

        self::assertSame(
            [
                'CZK',
                'CZK',
                'CZK',
                'CZK',
            ],
            $items->pluck('currency')->all(),
        );

        self::assertSame(
            [
                1,
                2,
                3,
                4,
            ],
            $items->pluck('position')->all(),
        );

        self::assertSame(
            PriceListItem::UNIT_KM,
            $items->last()?->getAttribute('unit'),
        );

        self::assertSame(
            PriceListItem::CODE_ACTUAL_KM,
            $items->last()->getAttribute(
                'quantity_source',
            ),
        );
    }

    public function test_provider_cannot_create_billing_price_list_for_foreign_relationship(): void
    {
        $foundation = $this->foundation();

        $foreignProvider = $this->organization('Foreign Provider');
        $foreignCustomer = $this->organization('Foreign Customer');

        $foreignRelationship =
            OrganizationRelationship::query()->create([
                'source_organization_id' => $foreignCustomer->getKey(),
                'target_organization_id' => $foreignProvider->getKey(),
                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                'status' => OrganizationRelationship::STATUS_ACTIVE,
                'valid_from' => now()->subDay()->toDateString(),
                'valid_until' => null,
            ]);

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $this
            ->postJson(
                '/api/v1/customers/'.
                $foreignRelationship->getKey().
                '/price-lists',
                [
                    'name' => 'Forbidden Billing',
                    'currency' => 'CZK',
                    'valid_from' => '2026-01-01',
                    'items' => [
                        [
                            'code' => PriceListItem::CODE_DELIVERED_PARCELS,
                            'description' => 'Doručená zásilka',
                            'unit_rate' => '12.5000',
                        ],
                        [
                            'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                            'description' => 'Přesměrovaná zásilka',
                            'unit_rate' => '8.0000',
                        ],
                        [
                            'code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
                            'description' => 'Nedoručená zásilka',
                            'unit_rate' => '3.0000',
                        ],
                        [
                            'code' => PriceListItem::CODE_ACTUAL_KM,
                            'description' => 'Skutečný kilometr',
                            'unit_rate' => '5.2500',
                        ],
                    ],
                ],
            )
            ->assertNotFound();

        self::assertSame(
            0,
            PriceList::query()->count(),
        );
    }

    public function test_provider_creates_new_customer_from_ares_in_incoming_direction(): void
    {
        $foundation = $this->foundation();

        Http::fake([
            'https://ares.gov.cz/*' => Http::response(
                [
                    'ico' => '12345678',
                    'obchodniJmeno' => 'Test Customer s.r.o.',
                    'dic' => 'CZ12345678',
                    'seznamRegistraci' => [
                        'stavZdrojeDph' => 'AKTIVNI',
                    ],
                    'sidlo' => [
                        'nazevUlice' => 'Testovací',
                        'cisloDomovni' => 10,
                        'cisloOrientacni' => 2,
                        'nazevObce' => 'Praha',
                        'psc' => 11000,
                        'kodStatu' => 'CZ',
                    ],
                ],
                200,
            ),
        ]);

        $organizationCountBefore =
            Organization::query()->count();

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $response = $this->postJson(
            '/api/v1/customers',
            [
                'registration_number' => '12345678',
                'relationship_valid_from' => '2026-01-01',
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.customer.name',
                'Test Customer s.r.o.',
            )
            ->assertJsonPath(
                'data.customer.type',
                Organization::TYPE_CARRIER,
            )
            ->assertJsonPath(
                'data.customer.registration_number',
                '12345678',
            )
            ->assertJsonPath(
                'data.relationship_valid_from',
                '2026-01-01',
            );

        $customer =
            Organization::query()
                ->where(
                    'registration_number',
                    '12345678',
                )
                ->sole();

        self::assertSame(
            Organization::TYPE_CARRIER,
            $customer->getAttribute('type'),
        );

        self::assertSame(
            $organizationCountBefore + 1,
            Organization::query()->count(),
        );

        $relationship =
            OrganizationRelationship::query()
                ->where(
                    'source_organization_id',
                    $customer->getKey(),
                )
                ->where(
                    'target_organization_id',
                    $foundation['provider']->getKey(),
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->sole();

        self::assertSame(
            OrganizationRelationship::STATUS_ACTIVE,
            $relationship->getAttribute('status'),
        );

        Http::assertSentCount(1);
    }

    public function test_provider_reuses_existing_organization_for_customer_role_without_duplicate(): void
    {
        $foundation = $this->foundation();

        $existingCustomer =
            $this->organization(
                'Existing Customer',
            );

        $existingCustomer->forceFill([
            'registration_number' => '87654321',
        ])->save();

        $organizationCountBefore =
            Organization::query()->count();

        Http::fake();

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $response = $this->postJson(
            '/api/v1/customers',
            [
                'registration_number' => '87654321',
                'relationship_valid_from' => '2026-02-01',
            ],
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.customer.id',
                $existingCustomer->getKey(),
            );

        self::assertSame(
            $organizationCountBefore,
            Organization::query()->count(),
        );

        $relationship =
            OrganizationRelationship::query()
                ->where(
                    'source_organization_id',
                    $existingCustomer->getKey(),
                )
                ->where(
                    'target_organization_id',
                    $foundation['provider']->getKey(),
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->sole();

        self::assertSame(
            $existingCustomer->getKey(),
            $relationship->getAttribute(
                'source_organization_id',
            ),
        );

        self::assertSame(
            $foundation['provider']->getKey(),
            $relationship->getAttribute(
                'target_organization_id',
            ),
        );

        Http::assertNothingSent();
    }

    public function test_provider_rejects_duplicate_active_customer_relationship(): void
    {
        $foundation = $this->foundation();

        $existingCustomer =
            $this->organization(
                'Duplicate Customer',
            );

        $existingCustomer->forceFill([
            'registration_number' => '87654321',
        ])->save();

        OrganizationRelationship::query()->create([
            'source_organization_id' => $existingCustomer->getKey(),
            'target_organization_id' => $foundation['provider']->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay()->startOfDay(),
            'valid_until' => null,
        ]);

        Http::fake();

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $this->postJson(
            '/api/v1/customers',
            [
                'registration_number' => '87654321',
                'relationship_valid_from' => '2026-03-01',
            ],
        )->assertUnprocessable();

        self::assertSame(
            1,
            OrganizationRelationship::query()
                ->where(
                    'source_organization_id',
                    $existingCustomer->getKey(),
                )
                ->where(
                    'target_organization_id',
                    $foundation['provider']->getKey(),
                )
                ->where(
                    'relationship_type',
                    OrganizationRelationship::TYPE_SUBCONTRACTING,
                )
                ->count(),
        );

        Http::assertNothingSent();
    }

    public function test_provider_billing_draft_requires_complete_canonical_item_set_atomically(): void
    {
        $foundation = $this->foundation();

        $this->authenticate(
            $foundation['user'],
            $foundation['provider'],
        );

        $this->postJson(
            '/api/v1/customers/'.
            $foundation['relationship']->getKey().
            '/price-lists',
            [
                'name' => 'Incomplete Billing',
                'currency' => 'CZK',
                'valid_from' => '2026-01-01',
                'items' => [
                    [
                        'code' => PriceListItem::CODE_DELIVERED_PARCELS,
                        'unit_rate' => '12.5000',
                    ],
                    [
                        'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                        'unit_rate' => '8.0000',
                    ],
                    [
                        'code' => PriceListItem::CODE_ACTUAL_KM,
                        'unit_rate' => '5.2500',
                    ],
                ],
            ],
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items',
            ]);

        $this->assertDatabaseCount(
            'price_lists',
            0,
        );

        $this->assertDatabaseCount(
            'price_list_versions',
            0,
        );

        $this->assertDatabaseCount(
            'price_list_items',
            0,
        );
    }

    /**
     * @return array{
     *     user: User,
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship
     * }
     */
    private function foundation(): array
    {
        $user = User::factory()->create();

        $customer = $this->organization('Customer');
        $provider = $this->organization('Provider');

        OrganizationMembership::query()->create([
            'organization_id' => $provider->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_OWNER,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $relationship =
            OrganizationRelationship::query()->create([
                'source_organization_id' => $customer->getKey(),
                'target_organization_id' => $provider->getKey(),
                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                'status' => OrganizationRelationship::STATUS_ACTIVE,
                'valid_from' => now()->subDay()->toDateString(),
                'valid_until' => null,
            ]);

        $this->grantPermissions(
            $user,
            $provider,
            [
                'pricing.view',
                'pricing.manage',
            ],
        );

        return [
            'user' => $user,
            'customer' => $customer,
            'provider' => $provider,
            'relationship' => $relationship,
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(
        User $user,
        Organization $organization,
        array $permissions,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            $registrar->forgetCachedPermissions();

            foreach ($permissions as $permissionName) {
                $permission = Permission::findOrCreate(
                    $permissionName,
                    'web',
                );

                $user->givePermissionTo($permission);
            }
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    private function authenticate(
        User $user,
        Organization $organization,
    ): void {
        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create([
            'name' => $name,
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
