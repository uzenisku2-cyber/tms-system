<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class OrganizationPeopleRoleProvisioner
{
    /** @var array<string, list<string>> */
    public const ROLES = [
        'carrier-admin' => [
            'people.manage', 'daily-reports.view', 'daily-reports.enter-for-driver',
            'daily-reports.review', 'daily-reports.request-correction',
            'daily-reports.approve', 'availability.confirm',
        ],
        'dispatcher' => [
            'daily-reports.view', 'daily-reports.enter-for-driver',
            'daily-reports.review', 'daily-reports.request-correction',
            'daily-reports.approve', 'availability.confirm',
        ],
        'driver' => [
            'daily-reports.view', 'daily-reports.create',
            'daily-reports.update', 'daily-reports.submit',
        ],
    ];

    /** @param list<string> $roles */
    public function assign(User $user, int $organizationId, array $roles): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($organizationId);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            foreach ($roles as $name) {
                $permissions = self::ROLES[$name] ?? null;
                if ($permissions === null) {
                    abort(422, 'Unknown organization role.');
                }
                foreach ($permissions as $permission) {
                    Permission::findOrCreate($permission, 'web');
                }
                $role = Role::findOrCreate($name, 'web');
                $role->syncPermissions($permissions);
                $user->assignRole($role);
            }
            $registrar->forgetCachedPermissions();
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }
}
