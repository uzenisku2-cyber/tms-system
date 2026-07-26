<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'sanctum';

        $permissions = [
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
            'vehicles.delete',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guard,
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => $guard,
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => $guard,
        ]);

        // FULL ACCESS
        $superAdmin->syncPermissions(Permission::all());

        // ADMIN
        $admin->syncPermissions([
            'vehicles.view',
            'vehicles.create',
            'vehicles.update',
        ]);

        // MANAGER
        $manager->syncPermissions([
            'vehicles.view',
            'vehicles.create',
        ]);

        // USER
        $user->syncPermissions([
            'vehicles.view',
        ]);
    }
}