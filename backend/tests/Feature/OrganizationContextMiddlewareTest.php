<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OrganizationContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth:sanctum',
            'organization',
        ])->get(
            '/api/_test/organization-context',
            static fn (
                OrganizationContext $context,
            ) => response()->json([
                'organization_id' => $context->requireId(),
            ]),
        );
    }

    public function test_active_member_can_select_organization(): void
    {
        [$user, $organization] = $this->createMembership();

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson('/api/_test/organization-context')
            ->assertOk()
            ->assertJson([
                'organization_id' => $organization->getKey(),
            ]);
    }

    public function test_missing_header_is_rejected(): void
    {
        [$user] = $this->createMembership();

        Sanctum::actingAs($user);

        $this->getJson('/api/_test/organization-context')
            ->assertStatus(400);
    }

    public function test_foreign_organization_is_rejected(): void
    {
        [$user] = $this->createMembership();

        $foreign = Organization::create([
            'name' => 'Foreign carrier',
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $foreign->getKey(),
        )->getJson('/api/_test/organization-context')
            ->assertForbidden();
    }

    public function test_suspended_membership_is_rejected(): void
    {
        [$user, $organization] = $this->createMembership(
            OrganizationMembership::STATUS_SUSPENDED,
        );

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson('/api/_test/organization-context')
            ->assertForbidden();
    }

    public function test_inactive_organization_is_rejected(): void
    {
        [$user, $organization] = $this->createMembership(
            OrganizationMembership::STATUS_ACTIVE,
            Organization::STATUS_SUSPENDED,
        );

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        )->getJson('/api/_test/organization-context')
            ->assertForbidden();
    }

    /**
     * @return array{User, Organization}
     */
    private function createMembership(
        string $membershipStatus =
            OrganizationMembership::STATUS_ACTIVE,
        string $organizationStatus =
            Organization::STATUS_ACTIVE,
    ): array {
        $user = User::factory()->create();

        $organization = Organization::create([
            'name' => 'Test organization',
            'type' => Organization::TYPE_MASTER,
            'status' => $organizationStatus,
        ]);

        OrganizationMembership::create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => $membershipStatus,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        return [$user, $organization];
    }
}
