<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class OrganizationRolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_roles_are_seeded_for_each_active_organization(): void
    {
        $first = $this->createOrganization(
            'First carrier',
        );

        $second = $this->createOrganization(
            'Second carrier',
        );

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        self::assertSame(
            16,
            DB::table('permissions')->count(),
        );

        self::assertSame(
            8,
            DB::table('roles')->count(),
        );

        self::assertSame(
            0,
            DB::table('roles')
                ->whereNull('organization_id')
                ->count(),
        );

        self::assertSame(
            4,
            DB::table('roles')
                ->where(
                    'organization_id',
                    $first->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            4,
            DB::table('roles')
                ->where(
                    'organization_id',
                    $second->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            8,
            DB::table('roles')
                ->where('guard_name', 'web')
                ->count(),
        );

        self::assertSame(
            16,
            DB::table('permissions')
                ->where('guard_name', 'web')
                ->count(),
        );

        $dailyReportPermissions = [
            'daily-reports.view',
            'daily-reports.create',
            'daily-reports.update',
            'daily-reports.submit',
            'daily-reports.enter-for-driver',
            'daily-reports.review',
            'daily-reports.request-correction',
            'daily-reports.approve',
            'daily-reports.close',
        ];

        $seededDailyReportPermissions = DB::table('permissions')
            ->whereIn('name', $dailyReportPermissions)
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        sort($dailyReportPermissions);

        self::assertSame(
            $dailyReportPermissions,
            $seededDailyReportPermissions,
        );

        $dailyReportPermissionIds = DB::table('permissions')
            ->whereIn('name', $dailyReportPermissions)
            ->pluck('id');

        self::assertSame(
            0,
            DB::table('role_has_permissions')
                ->whereIn(
                    'permission_id',
                    $dailyReportPermissionIds,
                )
                ->count(),
        );

        $pricingManagePermissionId = DB::table('permissions')
            ->where('name', 'pricing.manage')
            ->where('guard_name', 'web')
            ->value('id');

        self::assertIsInt($pricingManagePermissionId);

        self::assertSame(
            2,
            DB::table('role_has_permissions')
                ->join(
                    'roles',
                    'roles.id',
                    '=',
                    'role_has_permissions.role_id',
                )
                ->where(
                    'role_has_permissions.permission_id',
                    $pricingManagePermissionId,
                )
                ->where('roles.name', 'super-admin')
                ->count(),
        );

        self::assertSame(
            0,
            DB::table('role_has_permissions')
                ->join(
                    'roles',
                    'roles.id',
                    '=',
                    'role_has_permissions.role_id',
                )
                ->where(
                    'role_has_permissions.permission_id',
                    $pricingManagePermissionId,
                )
                ->where('roles.name', '!=', 'super-admin')
                ->count(),
        );

        self::assertSame(
            26,
            DB::table('role_has_permissions')->count(),
        );

        self::assertSame(
            2,
            DB::table('roles')
                ->where('name', 'admin')
                ->distinct()
                ->count('organization_id'),
        );

        self::assertNull(
            app(PermissionRegistrar::class)
                ->getPermissionsTeamId(),
        );
    }

    private function createOrganization(
        string $name,
    ): Organization {
        return Organization::create([
            'name' => $name,
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }
}
