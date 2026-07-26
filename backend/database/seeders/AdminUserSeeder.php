<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // create roles if not exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@tms.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
            ]
        );

        // assign role
        $user->assignRole($adminRole);

        $this->command->info('Admin user created: admin@tms.local / password');
    }
}