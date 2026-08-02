<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BootstrapAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        config()->set([
            'tms.bootstrap.enabled' => false,
            'tms.bootstrap.organization_name' => null,
            'tms.bootstrap.owner_user_id' => null,
            'tms.bootstrap.owner_name' => null,
            'tms.bootstrap.owner_email' => null,
            'tms.bootstrap.owner_password' => null,
            'tms.bootstrap.admin_user_id' => null,
            'tms.bootstrap.admin_name' => null,
            'tms.bootstrap.admin_email' => null,
            'tms.bootstrap.admin_password' => null,
        ]);

        parent::tearDown();
    }

    public function test_bootstrap_is_disabled_by_default(): void
    {
        config()->set(
            'tms.bootstrap.enabled',
            false,
        );

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount(
            'organization_memberships',
            0,
        );
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('permissions', 0);
    }

    public function test_bootstrap_creates_separate_owner_and_super_admin(): void
    {
        $this->configureNewAccountsBootstrap();

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $organization = Organization::query()->sole();

        $owner = User::query()
            ->where('email', 'owner@example.test')
            ->sole();

        $administrator = User::query()
            ->where('email', 'admin@example.test')
            ->sole();

        self::assertTrue($organization->isActive());
        self::assertTrue($owner->isActive());
        self::assertTrue($administrator->isActive());

        self::assertTrue(
            Hash::check(
                'Owner-Correct-Horse-Battery-2026',
                $owner->password,
            ),
        );

        self::assertTrue(
            Hash::check(
                'Admin-Correct-Horse-Battery-2026',
                $administrator->password,
            ),
        );

        $this->assertMembership(
            $organization,
            $owner,
            OrganizationMembership::RELATIONSHIP_OWNER,
        );

        $this->assertMembership(
            $organization,
            $administrator,
            OrganizationMembership::RELATIONSHIP_REPRESENTATIVE,
        );

        $this->assertScopedSuperAdmin(
            $organization,
            $administrator,
        );

        $this->assertDatabaseMissing('model_has_roles', [
            'organization_id' => $organization->getKey(),
            'model_id' => $owner->getKey(),
            'model_type' => User::class,
        ]);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount(
            'organization_memberships',
            2,
        );
        $this->assertDatabaseCount('permissions', 18);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount(
            'role_has_permissions',
            15,
        );
        $this->assertDatabaseCount('model_has_roles', 1);

        self::assertSame(
            0,
            DB::table('roles')
                ->whereNull('organization_id')
                ->count(),
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    public function test_existing_accounts_by_id_require_no_credentials(): void
    {
        $owner = User::factory()->create([
            'name' => 'Existing Owner',
            'email' => 'existing-owner@example.test',
            'password' => Hash::make(
                'Existing-Owner-Password-Remains',
            ),
        ]);

        $administrator = User::factory()->create([
            'name' => 'Existing Administrator',
            'email' => 'existing-admin@example.test',
            'password' => Hash::make(
                'Existing-Admin-Password-Remains',
            ),
        ]);

        $this->configureExistingAccountsBootstrap(
            (int) $owner->getKey(),
            (int) $administrator->getKey(),
        );

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $owner->refresh();
        $administrator->refresh();

        self::assertSame('Existing Owner', $owner->name);

        self::assertTrue(
            Hash::check(
                'Existing-Owner-Password-Remains',
                $owner->password,
            ),
        );

        self::assertSame(
            'Existing Administrator',
            $administrator->name,
        );

        self::assertTrue(
            Hash::check(
                'Existing-Admin-Password-Remains',
                $administrator->password,
            ),
        );

        $organization = Organization::query()->sole();

        $this->assertMembership(
            $organization,
            $owner,
            OrganizationMembership::RELATIONSHIP_OWNER,
        );

        $this->assertMembership(
            $organization,
            $administrator,
            OrganizationMembership::RELATIONSHIP_REPRESENTATIVE,
        );

        $this->assertScopedSuperAdmin(
            $organization,
            $administrator,
        );

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount(
            'organization_memberships',
            2,
        );
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public function test_existing_accounts_by_email_are_not_overwritten(): void
    {
        $owner = User::factory()->create([
            'name' => 'Preserved Owner',
            'email' => 'owner@example.test',
            'password' => Hash::make(
                'Preserved-Owner-Password',
            ),
        ]);

        $administrator = User::factory()->create([
            'name' => 'Preserved Administrator',
            'email' => 'admin@example.test',
            'password' => Hash::make(
                'Preserved-Admin-Password',
            ),
        ]);

        $this->configureNewAccountsBootstrap();

        $this->seed(AdminUserSeeder::class);

        $owner->refresh();
        $administrator->refresh();

        self::assertSame('Preserved Owner', $owner->name);

        self::assertTrue(
            Hash::check(
                'Preserved-Owner-Password',
                $owner->password,
            ),
        );

        self::assertSame(
            'Preserved Administrator',
            $administrator->name,
        );

        self::assertTrue(
            Hash::check(
                'Preserved-Admin-Password',
                $administrator->password,
            ),
        );

        $organization = Organization::query()->sole();

        $this->assertMembership(
            $organization,
            $owner,
            OrganizationMembership::RELATIONSHIP_OWNER,
        );

        $this->assertMembership(
            $organization,
            $administrator,
            OrganizationMembership::RELATIONSHIP_REPRESENTATIVE,
        );

        $this->assertScopedSuperAdmin(
            $organization,
            $administrator,
        );
    }

    public function test_same_account_is_rejected_atomically(): void
    {
        $user = User::factory()->create();

        $this->configureExistingAccountsBootstrap(
            (int) $user->getKey(),
            (int) $user->getKey(),
        );

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('organizations', 0);
            $this->assertDatabaseCount(
                'organization_memberships',
                0,
            );
            $this->assertDatabaseCount('roles', 0);
            $this->assertDatabaseCount('permissions', 0);
            $this->assertDatabaseCount('model_has_roles', 0);
        }
    }

    public function test_unknown_owner_id_is_rejected_atomically(): void
    {
        $administrator = User::factory()->create();

        $this->configureExistingAccountsBootstrap(
            999999,
            (int) $administrator->getKey(),
        );

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('organizations', 0);
            $this->assertDatabaseCount(
                'organization_memberships',
                0,
            );
            $this->assertDatabaseCount('roles', 0);
            $this->assertDatabaseCount('permissions', 0);
        }
    }

    public function test_unknown_admin_id_is_rejected_atomically(): void
    {
        $owner = User::factory()->create();

        $this->configureExistingAccountsBootstrap(
            (int) $owner->getKey(),
            999999,
        );

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('organizations', 0);
            $this->assertDatabaseCount(
                'organization_memberships',
                0,
            );
            $this->assertDatabaseCount('roles', 0);
            $this->assertDatabaseCount('permissions', 0);
        }
    }

    public function test_weak_owner_password_is_rejected_atomically(): void
    {
        $this->configureNewAccountsBootstrap();

        config()->set(
            'tms.bootstrap.owner_password',
            'short',
        );

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('organizations', 0);
        }
    }

    public function test_weak_admin_password_is_rejected_atomically(): void
    {
        $this->configureNewAccountsBootstrap();

        config()->set(
            'tms.bootstrap.admin_password',
            'short',
        );

        $this->expectException(RuntimeException::class);

        try {
            $this->seed(AdminUserSeeder::class);
        } finally {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('organizations', 0);
        }
    }

    private function configureNewAccountsBootstrap(): void
    {
        config()->set([
            'tms.bootstrap.enabled' => true,
            'tms.bootstrap.organization_name' => 'TMS Master Organization',

            'tms.bootstrap.owner_user_id' => null,
            'tms.bootstrap.owner_name' => 'Bootstrap Owner',
            'tms.bootstrap.owner_email' => 'owner@example.test',
            'tms.bootstrap.owner_password' => 'Owner-Correct-Horse-Battery-2026',

            'tms.bootstrap.admin_user_id' => null,
            'tms.bootstrap.admin_name' => 'Bootstrap Administrator',
            'tms.bootstrap.admin_email' => 'admin@example.test',
            'tms.bootstrap.admin_password' => 'Admin-Correct-Horse-Battery-2026',
        ]);
    }

    private function configureExistingAccountsBootstrap(
        int $ownerUserId,
        int $adminUserId,
    ): void {
        config()->set([
            'tms.bootstrap.enabled' => true,
            'tms.bootstrap.organization_name' => 'TMS Master Organization',

            'tms.bootstrap.owner_user_id' => (string) $ownerUserId,
            'tms.bootstrap.owner_name' => null,
            'tms.bootstrap.owner_email' => null,
            'tms.bootstrap.owner_password' => null,

            'tms.bootstrap.admin_user_id' => (string) $adminUserId,
            'tms.bootstrap.admin_name' => null,
            'tms.bootstrap.admin_email' => null,
            'tms.bootstrap.admin_password' => null,
        ]);
    }

    private function assertMembership(
        Organization $organization,
        User $user,
        string $relationshipType,
    ): void {
        $this->assertDatabaseHas(
            'organization_memberships',
            [
                'organization_id' => $organization->getKey(),
                'user_id' => $user->getKey(),
                'relationship_type' => $relationshipType,
                'status' => OrganizationMembership::STATUS_ACTIVE,
            ],
        );
    }

    private function assertScopedSuperAdmin(
        Organization $organization,
        User $administrator,
    ): void {
        $this->assertDatabaseHas('roles', [
            'organization_id' => $organization->getKey(),
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'organization_id' => $organization->getKey(),
            'model_id' => $administrator->getKey(),
            'model_type' => User::class,
        ]);
    }
}
