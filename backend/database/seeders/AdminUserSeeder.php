<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class AdminUserSeeder extends Seeder
{
    private const GUARD = 'web';

    private const ROLE = 'super-admin';

    private const IDENTITY_OWNER = 'owner';

    private const IDENTITY_ADMIN = 'admin';

    public function run(): void
    {
        if (config('tms.bootstrap.enabled', false) !== true) {
            return;
        }

        $organizationName = $this->requiredConfiguration(
            'tms.bootstrap.organization_name',
        );

        $ownerUserId = $this->optionalPositiveIntegerConfiguration(
            'tms.bootstrap.owner_user_id',
        );

        $adminUserId = $this->optionalPositiveIntegerConfiguration(
            'tms.bootstrap.admin_user_id',
        );

        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId(null);

        try {
            DB::transaction(
                function () use (
                    $organizationName,
                    $ownerUserId,
                    $adminUserId,
                    $registrar,
                ): void {
                    $organization = $this->resolveOrganization(
                        $organizationName,
                    );

                    $owner = $this->resolveIdentity(
                        self::IDENTITY_OWNER,
                        $ownerUserId,
                    );

                    $administrator = $this->resolveIdentity(
                        self::IDENTITY_ADMIN,
                        $adminUserId,
                    );

                    $this->assertSeparateAccounts(
                        $owner,
                        $administrator,
                    );

                    $this->resolveMembership(
                        $organization,
                        $owner,
                        OrganizationMembership::RELATIONSHIP_OWNER,
                    );

                    $this->resolveMembership(
                        $organization,
                        $administrator,
                        OrganizationMembership::RELATIONSHIP_REPRESENTATIVE,
                    );

                    $this->call(RolePermissionSeeder::class);

                    $registrar->setPermissionsTeamId(
                        (int) $organization->getKey(),
                    );

                    $administrator->unsetRelation('roles');
                    $administrator->unsetRelation('permissions');

                    $role = Role::findOrCreate(
                        self::ROLE,
                        self::GUARD,
                    );

                    $administrator->assignRole($role);
                },
            );
        } finally {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }
    }

    private function resolveOrganization(
        string $organizationName,
    ): Organization {
        $organization = Organization::query()
            ->where('name', $organizationName)
            ->first();

        if ($organization === null) {
            $organization = Organization::create([
                'name' => $organizationName,
                'type' => Organization::TYPE_MASTER,
                'status' => Organization::STATUS_ACTIVE,
            ]);
        }

        if (
            $organization->type !==
            Organization::TYPE_MASTER
        ) {
            throw new RuntimeException(
                'Bootstrap organization must have the master type.',
            );
        }

        if (! $organization->isActive()) {
            throw new RuntimeException(
                'Bootstrap organization must be active.',
            );
        }

        return $organization;
    }

    private function resolveIdentity(
        string $identity,
        ?int $userId,
    ): User {
        if ($userId !== null) {
            return $this->resolveExistingAccount(
                $identity,
                $userId,
            );
        }

        return $this->resolveConfiguredAccount($identity);
    }

    private function resolveConfiguredAccount(
        string $identity,
    ): User {
        $name = $this->requiredConfiguration(
            "tms.bootstrap.{$identity}_name",
        );

        $email = strtolower(
            $this->requiredConfiguration(
                "tms.bootstrap.{$identity}_email",
            ),
        );

        $password = $this->requiredConfiguration(
            "tms.bootstrap.{$identity}_password",
        );

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            throw new RuntimeException(
                "Bootstrap {$identity} email is invalid.",
            );
        }

        if (strlen($password) < 16) {
            throw new RuntimeException(
                "Bootstrap {$identity} password must contain at least 16 characters.",
            );
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user === null) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }

        $this->assertActiveUser($identity, $user);

        return $user;
    }

    private function resolveExistingAccount(
        string $identity,
        int $userId,
    ): User {
        $user = User::query()->find($userId);

        if ($user === null) {
            throw new RuntimeException(
                "Configured bootstrap {$identity} user does not exist.",
            );
        }

        $this->assertActiveUser($identity, $user);

        return $user;
    }

    private function assertSeparateAccounts(
        User $owner,
        User $administrator,
    ): void {
        if (
            (int) $owner->getKey() ===
            (int) $administrator->getKey()
        ) {
            throw new RuntimeException(
                'Bootstrap owner and administrator must be different users.',
            );
        }
    }

    private function resolveMembership(
        Organization $organization,
        User $user,
        string $relationshipType,
    ): OrganizationMembership {
        $membership = OrganizationMembership::query()
            ->firstOrCreate(
                [
                    'organization_id' => $organization->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'relationship_type' => $relationshipType,
                    'status' => OrganizationMembership::STATUS_ACTIVE,
                    'valid_from' => now()->subSecond(),
                    'valid_until' => null,
                ],
            );

        if (
            $membership->relationship_type !==
            $relationshipType
        ) {
            throw new RuntimeException(
                'Bootstrap membership has an unexpected relationship type.',
            );
        }

        if (! $membership->isActiveAt()) {
            throw new RuntimeException(
                'Bootstrap membership must be active.',
            );
        }

        return $membership;
    }

    private function assertActiveUser(
        string $identity,
        User $user,
    ): void {
        if (! $user->isActive()) {
            throw new RuntimeException(
                "Bootstrap {$identity} user must be active.",
            );
        }
    }

    private function requiredConfiguration(
        string $key,
    ): string {
        $value = config($key);

        if (! is_string($value)) {
            throw new RuntimeException(
                "Missing bootstrap configuration: {$key}.",
            );
        }

        $value = trim($value);

        if ($value === '') {
            throw new RuntimeException(
                "Missing bootstrap configuration: {$key}.",
            );
        }

        return $value;
    }

    private function optionalPositiveIntegerConfiguration(
        string $key,
    ): ?int {
        $value = config($key);

        if ($value === null || $value === '') {
            return null;
        }

        $integer = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if ($integer === false) {
            throw new RuntimeException(
                "Bootstrap configuration {$key} must be a positive integer.",
            );
        }

        return $integer;
    }
}
