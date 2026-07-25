<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class OrganizationPermissionTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        Route::middleware(['auth:sanctum', 'organization'])
            ->match(
                ['GET', 'POST'],
                '/api/_test/organization-role',
                static function (): array {
                    $user = request()->user();

                    if (! $user instanceof User) {
                        abort(401);
                    }

                    if (request()->isMethod('post')) {
                        $role = Role::findOrCreate(
                            'dispatcher',
                            'web',
                        );

                        $user->assignRole($role);
                    }

                    return [
                        'has_role' => $user->hasRole('dispatcher'),
                        'organization_id' => getPermissionsTeamId(),
                    ];
                },
            );
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_roles_are_isolated_by_organization(): void
    {
        self::assertTrue(config('permission.teams'));

        self::assertSame(
            'organization_id',
            config('permission.column_names.team_foreign_key'),
        );

        self::assertSame(
            Organization::class,
            config('permission.models.team'),
        );

        $user = User::factory()->create();

        $first = $this->createOrganizationFor(
            $user,
            'First carrier',
        );

        $second = $this->createOrganizationFor(
            $user,
            'Second carrier',
        );

        Sanctum::actingAs($user);

        $this->withHeader(
            'X-Organization-ID',
            (string) $first->getKey(),
        )->postJson('/api/_test/organization-role')
            ->assertOk()
            ->assertJson([
                'has_role' => true,
                'organization_id' => $first->getKey(),
            ]);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $this->withHeader(
            'X-Organization-ID',
            (string) $second->getKey(),
        )->getJson('/api/_test/organization-role')
            ->assertOk()
            ->assertJson([
                'has_role' => false,
                'organization_id' => $second->getKey(),
            ]);

        $this->withHeader(
            'X-Organization-ID',
            (string) $second->getKey(),
        )->postJson('/api/_test/organization-role')
            ->assertOk()
            ->assertJson([
                'has_role' => true,
                'organization_id' => $second->getKey(),
            ]);

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('model_has_roles', 2);

        self::assertSame(
            2,
            DB::table('roles')
                ->where('name', 'dispatcher')
                ->distinct()
                ->count('organization_id'),
        );

        self::assertSame(
            2,
            DB::table('model_has_roles')
                ->where('model_id', $user->getKey())
                ->distinct()
                ->count('organization_id'),
        );
    }

    private function createOrganizationFor(
        User $user,
        string $name,
    ): Organization {
        $organization = Organization::create([
            'name' => $name,
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        OrganizationMembership::create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        return $organization;
    }
}
