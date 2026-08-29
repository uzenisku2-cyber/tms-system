<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class FuelSurchargePreviewSeeder extends Seeder
{
    private const PREVIEW_EMAIL = 'preview.s039@drayvia.local';

    private const DRIVER_EMAIL = 'dominik.preview@drayvia.local';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Fuel surcharge preview data may only be seeded locally.',
            );
        }

        DB::transaction(function (): void {
            $organizationId = $this->masterOrganizationId();
            $previewUser = $this->previewUser();

            $this->activeMembership(
                $organizationId,
                (int) $previewUser->getKey(),
                'employee',
            );
            $this->grantPreviewPermissions($previewUser, $organizationId);

            $customerId = $this->organization(
                'S039-CUSTOMER',
                'Zásilkovna – ukázkový odběratel',
                'master',
            );
            $this->relationship($customerId, $organizationId);

            $driverUserId = $this->driverUserId();
            $this->activeMembership($organizationId, $driverUserId, 'employee');
            $driverId = $this->driverId($driverUserId);
            $this->driverAssignment($driverId, $organizationId);
            $this->supervisoryScope(
                $organizationId,
                (int) $previewUser->getKey(),
            );

            $carrierId = $this->organization(
                'S039-CARRIER',
                'Náhledový externí dopravce',
                'subcontractor',
            );
            $this->relationship($organizationId, $carrierId);
        });
    }

    private function masterOrganizationId(): int
    {
        $id = DB::table('organizations')
            ->where('registration_number', 'S039-OWNER')
            ->value('id');

        $id ??= DB::table('organizations')
            ->where('type', 'master')
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');

        if ($id === null) {
            $id = DB::table('organizations')->insertGetId([
                'name' => 'DRAYVIA – izolovaný náhled',
                'type' => 'master',
                'status' => 'active',
                'registration_number' => 'S039-OWNER',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('organizations')->where('id', $id)->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);
        }

        return (int) $id;
    }

    private function previewUser(): User
    {
        return User::query()->updateOrCreate(
            ['email' => self::PREVIEW_EMAIL],
            [
                'name' => 'Správce náhledu S039',
                'password' => Hash::make('Nahled-S039!'),
                'status' => 'active',
            ],
        );
    }

    private function driverUserId(): int
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::DRIVER_EMAIL],
            [
                'name' => 'Dominik Náhled',
                'password' => Hash::make('Nahled-S039-Driver!'),
                'status' => 'active',
            ],
        );

        return (int) $user->getKey();
    }

    private function grantPreviewPermissions(
        User $user,
        int $organizationId,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($organizationId);

        try {
            foreach ([
                'compensation.manage',
                'compensation.view',
                'drivers.manage',
                'drivers.view',
                'fuel.manage',
                'fuel.view',
                'pricing.manage',
                'pricing.view',
                'users.manage',
                'users.view',
            ] as $name) {
                $permission = Permission::findOrCreate($name, 'web');
                $user->givePermissionTo($permission);
            }
        } finally {
            $registrar->setPermissionsTeamId(null);
            $registrar->forgetCachedPermissions();
        }
    }

    private function organization(
        string $registrationNumber,
        string $name,
        string $type,
    ): int {
        $existing = DB::table('organizations')
            ->where('registration_number', $registrationNumber)
            ->value('id');

        $existing ??= DB::table('organizations')
            ->where('name', $name)
            ->value('id');

        $values = [
            'name' => $name,
            'type' => $type,
            'status' => 'active',
            'registration_number' => $registrationNumber,
            'updated_at' => now(),
        ];

        if ($existing !== null) {
            DB::table('organizations')->where('id', $existing)->update($values);

            return (int) $existing;
        }

        return (int) DB::table('organizations')->insertGetId([
            ...$values,
            'created_at' => now(),
        ]);
    }

    private function activeMembership(
        int $organizationId,
        int $userId,
        string $relationshipType,
    ): void {
        DB::table('organization_memberships')->updateOrInsert(
            [
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ],
            [
                'relationship_type' => $relationshipType,
                'status' => 'active',
                'valid_from' => '2026-08-01 00:00:00',
                'valid_until' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function relationship(int $sourceId, int $targetId): void
    {
        DB::table('organization_relationships')->updateOrInsert(
            [
                'source_organization_id' => $sourceId,
                'target_organization_id' => $targetId,
                'relationship_type' => 'subcontracting',
            ],
            [
                'status' => 'active',
                'valid_from' => '2026-08-01 00:00:00',
                'valid_until' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function driverId(int $userId): int
    {
        $existing = DB::table('drivers')
            ->where('user_id', $userId)
            ->value('id');

        $values = [
            'first_name' => 'Dominik',
            'last_name' => 'Náhled',
            'email' => self::DRIVER_EMAIL,
            'license_number' => 'S039-PREVIEW',
            'license_category' => 'B',
            'active' => true,
            'status' => 'active',
            'updated_at' => now(),
        ];

        if ($existing !== null) {
            DB::table('drivers')->where('id', $existing)->update($values);

            return (int) $existing;
        }

        return (int) DB::table('drivers')->insertGetId([
            ...$values,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    private function driverAssignment(int $driverId, int $organizationId): void
    {
        DB::table('driver_organization_assignments')->updateOrInsert(
            ['driver_id' => $driverId, 'valid_until' => null],
            [
                'organization_id' => $organizationId,
                'employment_type' => 'employee',
                'valid_from' => '2026-08-01',
                'created_by_user_id' => $this->previewUser()->getKey(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function supervisoryScope(
        int $organizationId,
        int $previewUserId,
    ): void {
        DB::table('driver_supervisory_scopes')->updateOrInsert(
            [
                'organization_id' => $organizationId,
                'supervisor_user_id' => $previewUserId,
                'scope_type' => 'organization',
                'target_organization_id' => $organizationId,
                'valid_until' => null,
            ],
            [
                'target_driver_id' => null,
                'organization_relationship_id' => null,
                'valid_from' => '2026-08-01',
                'created_by_user_id' => $previewUserId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
