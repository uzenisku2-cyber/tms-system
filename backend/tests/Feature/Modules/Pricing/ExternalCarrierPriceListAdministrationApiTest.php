<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class ExternalCarrierPriceListAdministrationApiTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX_URL = '/api/v1/external-carriers';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_read_routes_require_authentication_context_and_permission(): void
    {
        $this->getJson(self::INDEX_URL)
            ->assertUnauthorized();

        [$user, $master] = $this->context();

        Sanctum::actingAs($user);

        $this->getJson(self::INDEX_URL)
            ->assertStatus(400);

        $this->withOrganization($master)
            ->getJson(self::INDEX_URL)
            ->assertForbidden();
    }

    public function test_write_routes_require_manage_permission(): void
    {
        [$user, $master] = $this->context();
        $this->grantViewPermission($user, $master);

        $carrier = $this->organization(
            'Read-only External Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$relationship->getKey().'/price-lists',
                $this->completeDraftPayload(),
            )
            ->assertForbidden();

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
    }

    public function test_index_lists_only_active_outgoing_external_carriers_with_price_lists(): void
    {
        [$user, $master] = $this->context();
        $this->grantViewPermission($user, $master);

        $carrier = $this->organization(
            'External Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        $priceList = $this->priceList(
            $relationship,
            $master,
            $carrier,
            $user,
            'Carrier tariff',
        );

        $providerManagedPriceList = $this->priceList(
            $relationship,
            $master,
            $carrier,
            $user,
            'Provider-managed carrier tariff',
            $carrier,
        );

        PriceListVersion::query()->create([
            'price_list_id' => $priceList->getKey(),
            'version_number' => 1,
            'lock_version' => 1,
            'status' => PriceListVersion::STATUS_DRAFT,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => null,
            'created_by_user_id' => $user->getKey(),
        ]);

        $endedCarrier = $this->organization(
            'Ended Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $this->relationship(
            $master,
            $endedCarrier,
            OrganizationRelationship::STATUS_ENDED,
        );

        $futureCarrier = $this->organization(
            'Future Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $this->relationship(
            $master,
            $futureCarrier,
            OrganizationRelationship::STATUS_ACTIVE,
            now()->addDay()->toDateString(),
        );

        $incomingCustomer = $this->organization(
            'Incoming Customer',
            Organization::TYPE_CARRIER,
        );

        $this->relationship(
            $incomingCustomer,
            $master,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($master)
            ->getJson(self::INDEX_URL)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.price_lists')
            ->assertJsonMissingPath('data.0.external_carrier.id')
            ->assertJsonMissing([
                'public_id' => $providerManagedPriceList->getRouteKey(),
            ])
            ->assertJsonPath(
                'data.0.relationship_id',
                $relationship->getKey(),
            )
            ->assertJsonPath(
                'data.0.external_carrier.name',
                'External Carrier',
            )
            ->assertJsonPath(
                'data.0.price_lists.0.public_id',
                $priceList->getRouteKey(),
            )
            ->assertJsonPath(
                'data.0.price_lists.0.managed_by_customer',
                true,
            )
            ->assertJsonPath(
                'data.0.price_lists.0.versions.0.version_number',
                1,
            );
    }

    public function test_show_is_source_scoped_and_cannot_open_foreign_or_incoming_relationship(): void
    {
        [$user, $master] = $this->context();
        $this->grantViewPermission($user, $master);

        $carrier = $this->organization(
            'Scoped Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        $foreignMaster = $this->organization(
            'Foreign Master',
            Organization::TYPE_MASTER,
        );

        $foreignCarrier = $this->organization(
            'Foreign Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );

        $foreignRelationship = $this->relationship(
            $foreignMaster,
            $foreignCarrier,
        );

        $incomingCustomer = $this->organization(
            'Billing Customer',
            Organization::TYPE_CARRIER,
        );

        $incomingRelationship = $this->relationship(
            $incomingCustomer,
            $master,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($master)
            ->getJson(
                self::INDEX_URL.'/'.$relationship->getKey(),
            )
            ->assertOk()
            ->assertJsonPath(
                'data.external_carrier.name',
                'Scoped Carrier',
            );

        $this->withOrganization($master)
            ->getJson(
                self::INDEX_URL.'/'.$foreignRelationship->getKey(),
            )
            ->assertNotFound();

        $this->withOrganization($master)
            ->getJson(
                self::INDEX_URL.'/'.$incomingRelationship->getKey(),
            )
            ->assertNotFound();
    }

    public function test_store_creates_complete_customer_managed_draft_atomically(): void
    {
        [$user, $master] = $this->context();
        $this->grantManagePermission($user, $master);

        $carrier = $this->organization(
            'Managed External Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        Sanctum::actingAs($user);

        $response = $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$relationship->getKey().'/price-lists',
                $this->completeDraftPayload(),
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Carrier Compensation 2026')
            ->assertJsonPath('data.currency', 'CZK');

        $priceList = PriceList::query()->sole();

        self::assertSame(
            $relationship->getKey(),
            $priceList->getAttribute('organization_relationship_id'),
        );
        self::assertSame(
            $master->getKey(),
            $priceList->getAttribute('owner_organization_id'),
        );
        self::assertSame(
            $master->getKey(),
            $priceList->getAttribute('customer_organization_id'),
        );
        self::assertSame(
            $carrier->getKey(),
            $priceList->getAttribute('provider_organization_id'),
        );
        self::assertSame(
            $master->getKey(),
            $priceList->getAttribute('managed_by_organization_id'),
        );

        $version = $priceList->versions()->sole();

        self::assertSame(1, $version->getAttribute('version_number'));
        self::assertSame('draft', $version->getAttribute('status'));
        self::assertSame(4, $version->items()->count());
        self::assertSame(1, $version->conditionalRules()->count());

        $rule = $version->conditionalRules()->sole();

        self::assertSame(
            PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
            $rule->getAttribute('evaluation_scope'),
        );
        self::assertSame(
            PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            $rule->getAttribute('reward_method'),
        );
        self::assertSame(2, $rule->metricComponents()->count());
        self::assertSame(2, $rule->bands()->count());
    }

    public function test_store_is_source_scoped_and_rejects_incomplete_tree_atomically(): void
    {
        [$user, $master] = $this->context();
        $this->grantManagePermission($user, $master);

        $carrier = $this->organization(
            'Allowed Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $allowedRelationship = $this->relationship(
            $master,
            $carrier,
        );

        $foreignMaster = $this->organization(
            'Foreign Customer',
            Organization::TYPE_MASTER,
        );
        $foreignCarrier = $this->organization(
            'Foreign Provider',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $foreignRelationship = $this->relationship(
            $foreignMaster,
            $foreignCarrier,
        );

        $incomingCustomer = $this->organization(
            'Incoming Billing Customer',
            Organization::TYPE_CARRIER,
        );
        $incomingRelationship = $this->relationship(
            $incomingCustomer,
            $master,
        );

        Sanctum::actingAs($user);

        $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$foreignRelationship->getKey().'/price-lists',
                $this->completeDraftPayload(),
            )
            ->assertNotFound();

        $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$incomingRelationship->getKey().'/price-lists',
                $this->completeDraftPayload(),
            )
            ->assertNotFound();

        $incompletePayload = $this->completeDraftPayload();
        array_pop($incompletePayload['items']);

        $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$allowedRelationship->getKey().'/price-lists',
                $incompletePayload,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('price_lists', 0);
        $this->assertDatabaseCount('price_list_versions', 0);
        $this->assertDatabaseCount('price_list_items', 0);
        $this->assertDatabaseCount('price_list_conditional_rules', 0);
    }

    public function test_customer_managed_versions_support_atomic_edit_new_version_and_explicit_lifecycle(): void
    {
        [$user, $master] = $this->context();
        $this->grantManagePermission($user, $master);

        $carrier = $this->organization(
            'Lifecycle External Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        Sanctum::actingAs($user);

        $initial = $this->completeDraftPayload();
        $initial['valid_from'] = now()
            ->subMonth()
            ->startOfMonth()
            ->toDateString();

        $created = $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$relationship->getKey().'/price-lists',
                $initial,
            )
            ->assertCreated()
            ->assertJsonPath('data.current_version', 1);

        $publicId = $created->json('data.public_id');
        self::assertIsString($publicId);
        self::assertNotSame('', $publicId);

        $base = self::INDEX_URL.'/'.$relationship->getKey()
            .'/price-lists/'.$publicId.'/versions';

        $replacement = $this->completeDraftPayload();
        unset($replacement['currency']);
        $replacement['name'] = 'Lifecycle Carrier Compensation';
        $replacement['valid_from'] = $initial['valid_from'];
        $replacement['expected_lock_version'] = 1;
        $replacement['items'][0]['unit_rate'] = '36.0000';

        $this->withOrganization($master)
            ->putJson($base.'/1', $replacement)
            ->assertOk()
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.items.0.unit_rate', '36.0000');

        $this->withOrganization($master)
            ->postJson(
                $base.'/1/approve',
                ['expected_lock_version' => 2],
            )
            ->assertOk()
            ->assertJsonPath('data.status', PriceListVersion::STATUS_APPROVED);

        $this->withOrganization($master)
            ->postJson(
                $base.'/1/activate',
                ['expected_lock_version' => 2],
            )
            ->assertOk()
            ->assertJsonPath('data.status', PriceListVersion::STATUS_ACTIVE);

        $this->withOrganization($master)
            ->postJson(
                $base.'/1/expire',
                [
                    'expected_lock_version' => 2,
                    'valid_until' => now()->subDay()->toDateString(),
                ],
            )
            ->assertOk()
            ->assertJsonPath('data.status', PriceListVersion::STATUS_EXPIRED);

        $newVersion = $this->completeDraftPayload();
        $newVersion['name'] = 'Lifecycle Carrier Compensation v2';
        $newVersion['expected_current_version'] = 1;
        $newVersion['valid_from'] = now()->toDateString();
        $newVersion['items'][1]['unit_rate'] = '16.0000';

        $this->withOrganization($master)
            ->postJson($base, $newVersion)
            ->assertCreated()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.status', PriceListVersion::STATUS_DRAFT)
            ->assertJsonCount(4, 'data.items')
            ->assertJsonCount(1, 'data.conditional_rules');

        $priceList = PriceList::query()
            ->where('public_id', $publicId)
            ->sole();
        $draft = $priceList->versions()
            ->where('version_number', 2)
            ->sole();

        self::assertSame(2, $priceList->getAttribute('current_version'));
        self::assertSame(4, $draft->items()->count());
        self::assertSame(1, $draft->conditionalRules()->count());
        self::assertSame(
            2,
            $draft->conditionalRules()->sole()->bands()->count(),
        );
    }

    public function test_customer_managed_lifecycle_routes_cannot_escape_relationship_scope(): void
    {
        [$user, $master] = $this->context();
        $this->grantManagePermission($user, $master);

        $carrier = $this->organization(
            'Scoped Lifecycle Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $relationship = $this->relationship(
            $master,
            $carrier,
        );

        $foreignMaster = $this->organization(
            'Foreign Lifecycle Customer',
            Organization::TYPE_MASTER,
        );
        $foreignCarrier = $this->organization(
            'Foreign Lifecycle Carrier',
            Organization::TYPE_SUBCONTRACTOR,
        );
        $foreignRelationship = $this->relationship(
            $foreignMaster,
            $foreignCarrier,
        );

        $incomingCustomer = $this->organization(
            'Incoming Lifecycle Customer',
            Organization::TYPE_CARRIER,
        );
        $incomingRelationship = $this->relationship(
            $incomingCustomer,
            $master,
        );

        Sanctum::actingAs($user);

        $created = $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$relationship->getKey().'/price-lists',
                $this->completeDraftPayload(),
            )
            ->assertCreated();

        $publicId = $created->json('data.public_id');
        self::assertIsString($publicId);

        $replacement = $this->completeDraftPayload();
        unset($replacement['currency']);
        $replacement['expected_lock_version'] = 1;

        $this->withOrganization($master)
            ->putJson(
                self::INDEX_URL.'/'.$foreignRelationship->getKey()
                    .'/price-lists/'.$publicId.'/versions/1',
                $replacement,
            )
            ->assertNotFound();

        $this->withOrganization($master)
            ->postJson(
                self::INDEX_URL.'/'.$incomingRelationship->getKey()
                    .'/price-lists/'.$publicId.'/versions/1/approve',
                ['expected_lock_version' => 1],
            )
            ->assertNotFound();

        $version = PriceListVersion::query()->sole();

        self::assertSame(1, $version->getAttribute('lock_version'));
        self::assertSame(
            PriceListVersion::STATUS_DRAFT,
            $version->getAttribute('status'),
        );
        self::assertSame(4, $version->items()->count());
        self::assertSame(1, $version->conditionalRules()->count());
    }

    /** @return array<string, mixed> */
    private function completeDraftPayload(): array
    {
        return [
            'name' => 'Carrier Compensation 2026',
            'description' => 'External-carrier monthly compensation.',
            'currency' => 'czk',
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => 'Initial external-carrier tariff.',
            'items' => [
                [
                    'code' => PriceListItem::CODE_DELIVERED_PARCELS,
                    'description' => null,
                    'unit_rate' => '35.0000',
                ],
                [
                    'code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                    'description' => null,
                    'unit_rate' => '15.0000',
                ],
                [
                    'code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
                    'description' => null,
                    'unit_rate' => '0.0000',
                ],
                [
                    'code' => PriceListItem::CODE_ACTUAL_KM,
                    'description' => null,
                    'unit_rate' => '4.0000',
                ],
            ],
            'conditional_rules' => [
                [
                    'code' => 'redirected_share',
                    'name' => 'Redirected share bonus',
                    'description' => null,
                    'metric_type' => 'ratio_percentage',
                    'metric_numerator_sources' => [
                        'redirected_parcels',
                    ],
                    'metric_denominator_sources' => [
                        'loaded_parcels',
                    ],
                    'evaluation_scope' => 'monthly_price_list',
                    'reward_method' => 'amount_per_unit',
                    'reward_quantity_source' => 'redirected_parcels',
                    'reward_target_item_code' => null,
                    'rounding_scale' => 2,
                    'bands' => [
                        [
                            'minimum_value' => '30.0000',
                            'maximum_value' => '40.0000',
                            'minimum_inclusive' => true,
                            'maximum_inclusive' => false,
                            'adjustment_value' => '1.5000',
                        ],
                        [
                            'minimum_value' => '40.0000',
                            'maximum_value' => '100.0000',
                            'minimum_inclusive' => true,
                            'maximum_inclusive' => true,
                            'adjustment_value' => '3.0000',
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array{User, Organization} */
    private function context(): array
    {
        $user = User::factory()->create();
        $master = $this->organization(
            'Master Organization',
            Organization::TYPE_MASTER,
        );

        OrganizationMembership::query()->create([
            'organization_id' => $master->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        return [$user, $master];
    }

    private function organization(string $name, string $type): Organization
    {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function relationship(
        Organization $source,
        Organization $target,
        string $status = OrganizationRelationship::STATUS_ACTIVE,
        ?string $validFrom = null,
    ): OrganizationRelationship {
        return OrganizationRelationship::query()->create([
            'source_organization_id' => $source->getKey(),
            'target_organization_id' => $target->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => $status,
            'valid_from' => $validFrom ?? now()->subDay()->toDateString(),
            'valid_until' => $status === OrganizationRelationship::STATUS_ENDED
                ? now()->subDay()->toDateString()
                : null,
        ]);
    }

    private function priceList(
        OrganizationRelationship $relationship,
        Organization $customer,
        Organization $provider,
        User $creator,
        string $name,
        ?Organization $manager = null,
    ): PriceList {
        $manager = $manager ?? $customer;

        return PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $manager->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'managed_by_organization_id' => $manager->getKey(),
            'name' => $name,
            'description' => null,
            'currency' => 'CZK',
            'status' => PriceList::STATUS_DRAFT,
            'current_version' => 1,
            'created_by_user_id' => $creator->getKey(),
        ]);
    }

    private function grantManagePermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $previousOrganizationId = $registrar->getPermissionsTeamId();

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
            $registrar->setPermissionsTeamId($previousOrganizationId);
            $registrar->forgetCachedPermissions();
        }
    }

    private function grantViewPermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $previousOrganizationId = $registrar->getPermissionsTeamId();

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
            $registrar->setPermissionsTeamId($previousOrganizationId);
            $registrar->forgetCachedPermissions();
        }
    }

    private function withOrganization(Organization $organization): static
    {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
