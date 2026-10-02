<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class CarrierPriceListReadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_carrier_manager_sees_only_its_published_price_lists_without_write_access(): void
    {
        $master = $this->organization(Organization::TYPE_MASTER);
        $carrier = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $other = $this->organization(Organization::TYPE_SUBCONTRACTOR);
        $actor = User::factory()->create();
        $this->member($carrier, $actor);
        $this->seed(RolePermissionSeeder::class);
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $carrier->getKey());
        $actor->assignRole('carrier-admin');
        $registrar->setPermissionsTeamId(null);

        $visible = $this->priceList($master, $carrier, $actor, PriceList::STATUS_ACTIVE);
        $this->version($visible, $actor, PriceListVersion::STATUS_ACTIVE);
        $draft = $this->priceList($master, $carrier, $actor, PriceList::STATUS_DRAFT);
        $this->version($draft, $actor, PriceListVersion::STATUS_DRAFT);
        $foreign = $this->priceList($master, $other, $actor, PriceList::STATUS_ACTIVE);
        $this->version($foreign, $actor, PriceListVersion::STATUS_ACTIVE);

        Sanctum::actingAs($actor);
        $headers = ['X-Organization-ID' => (string) $carrier->getKey()];
        $response = $this->withHeaders($headers)->getJson('/api/v1/carrier/price-lists')->assertOk();
        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price_list.public_id', $visible->public_id)
            ->assertJsonPath('data.0.versions.0.status', PriceListVersion::STATUS_ACTIVE);
        $this->withHeaders($headers)->getJson('/api/v1/price-lists')->assertForbidden();
        $this->withHeaders($headers)->postJson('/api/v1/price-lists', [])->assertForbidden();
        $this->withHeaders(['X-Organization-ID' => (string) $other->getKey()])
            ->getJson('/api/v1/carrier/price-lists')->assertForbidden();
    }

    private function organization(string $type): Organization
    {
        return Organization::query()->create([
            'name' => 'Organization '.fake()->uuid(), 'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function member(Organization $organization, User $actor): void
    {
        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(), 'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => now()->subDay(),
        ]);
    }

    private function priceList(Organization $master, Organization $carrier, User $actor, string $status): PriceList
    {
        $relationship = OrganizationRelationship::query()->firstOrCreate([
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
        ], [
            'source_organization_id' => $master->getKey(),
            'target_organization_id' => $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
        ]);

        return PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $master->getKey(),
            'customer_organization_id' => $master->getKey(),
            'provider_organization_id' => $carrier->getKey(),
            'managed_by_organization_id' => $master->getKey(),
            'name' => 'Ceník '.fake()->uuid(), 'currency' => 'CZK',
            'status' => $status, 'created_by_user_id' => $actor->getKey(),
        ]);
    }

    private function version(PriceList $list, User $actor, string $status): void
    {
        PriceListVersion::query()->create([
            'price_list_id' => $list->getKey(), 'version_number' => 1,
            'status' => $status, 'valid_from' => $status === PriceListVersion::STATUS_ACTIVE ? now()->toDateString() : null,
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $status === PriceListVersion::STATUS_ACTIVE ? $actor->getKey() : null,
            'approved_at' => $status === PriceListVersion::STATUS_ACTIVE ? now() : null,
            'activated_at' => $status === PriceListVersion::STATUS_ACTIVE ? now() : null,
        ]);
    }
}
