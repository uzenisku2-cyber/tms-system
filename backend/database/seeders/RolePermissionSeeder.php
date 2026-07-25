<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    private const GUARD = 'web';

    /** @var list<string> */
    private const PERMISSIONS = [
        'vehicles.view',
        'vehicles.create',
        'vehicles.update',
        'vehicles.delete',
        'users.manage',
    ];

    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'super-admin' => [
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
            'vehicles.delete',
            'users.manage',
        ],
        'admin' => [
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
        ],
        'manager' => [
            'vehicles.view',
            'vehicles.create',
        ],
        'user' => [
            'vehicles.view',
        ],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();
        $registrar->setPermissionsTeamId(null);

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate(
                $permissionName,
                self::GUARD,
            );
        }

        try {
            Organization::query()
                ->where(
                    'status',
                    Organization::STATUS_ACTIVE,
                )
                ->orderBy('id')
                ->each(
                    function (
                        Organization $organization,
                    ) use ($registrar): void {
                        $organizationId = (int) $organization->getKey();

                        $registrar->setPermissionsTeamId(
                            $organizationId,
                        );

                        foreach (
                            self::ROLE_PERMISSIONS as $roleName => $permissions
                        ) {
                            $role = Role::findOrCreate(
                                $roleName,
                                self::GUARD,
                            );

                            $role->syncPermissions($permissions);
                        }
                    },
                );
        } finally {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }
    }
}
