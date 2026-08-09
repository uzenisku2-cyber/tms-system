<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Services;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class DriverRoleProvisioner
{
    public const ROLE_NAME = 'driver';

    /**
     * Intentionally excludes delegated entry, review, approval and closure.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'daily-reports.view',
        'daily-reports.create',
        'daily-reports.update',
        'daily-reports.submit',
    ];

    public function provision(
        int $organizationId,
    ): Role {
        $registrar = app(PermissionRegistrar::class);
        $previousOrganizationId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($organizationId);

            foreach (self::PERMISSIONS as $permissionName) {
                Permission::findOrCreate(
                    $permissionName,
                    'web',
                );
            }

            $role = Role::findOrCreate(
                self::ROLE_NAME,
                'web',
            );

            $role->syncPermissions(
                self::PERMISSIONS,
            );

            $registrar->forgetCachedPermissions();

            return $role;
        } finally {
            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );
        }
    }

    public function assign(
        User $user,
        int $organizationId,
    ): void {
        $role = $this->provision(
            $organizationId,
        );

        $registrar = app(PermissionRegistrar::class);
        $previousOrganizationId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                $organizationId,
            );

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $user->assignRole(
                $role,
            );
        } finally {
            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );
        }
    }
}
