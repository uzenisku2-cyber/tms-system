<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException as PermissionMigrationException;

return new class extends Migration
{
    private const TEAM_KEY = 'organization_id';

    public function up(): void
    {
        $presence = [
            Schema::hasColumn('roles', self::TEAM_KEY),
            Schema::hasColumn('model_has_roles', self::TEAM_KEY),
            Schema::hasColumn('model_has_permissions', self::TEAM_KEY),
        ];

        $allPresent = $presence === [true, true, true];
        $allAbsent = $presence === [false, false, false];

        if (! $allPresent && ! $allAbsent) {
            throw new PermissionMigrationException(
                'Permission team schema is only partially configured.',
            );
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite' && $allPresent) {
            return;
        }

        if ($driver !== 'pgsql') {
            throw new PermissionMigrationException(
                'Existing permission tables may only be upgraded on PostgreSQL.',
            );
        }

        if ($allAbsent) {
            $this->addOrganizationColumns();
        }

        $this->normalizePostgreSqlIndexes();
        $this->addOrganizationForeignKeys();

        if (! DB::connection()->pretending()) {
            $this->assertPostgreSqlSchema();
        }
    }

    public function down(): void
    {
        throw new PermissionMigrationException(
            'This forward-only organization permission migration cannot be rolled back.',
        );
    }

    private function addOrganizationColumns(): void
    {
        if (
            DB::table('model_has_roles')->exists()
            || DB::table('model_has_permissions')->exists()
        ) {
            throw new PermissionMigrationException(
                'Permission assignments require an organization backfill.',
            );
        }

        $statements = [
            'ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_name_guard_name_unique',
            'DROP INDEX IF EXISTS roles_name_guard_name_unique',
            'ALTER TABLE roles ADD COLUMN organization_id BIGINT NULL',

            'ALTER TABLE model_has_roles DROP CONSTRAINT model_has_roles_pkey',
            'ALTER TABLE model_has_roles ADD COLUMN organization_id BIGINT NOT NULL',
            'ALTER TABLE model_has_roles ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (organization_id, role_id, model_id, model_type)',

            'ALTER TABLE model_has_permissions DROP CONSTRAINT model_has_permissions_pkey',
            'ALTER TABLE model_has_permissions ADD COLUMN organization_id BIGINT NOT NULL',
            'ALTER TABLE model_has_permissions ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (organization_id, permission_id, model_id, model_type)',
        ];

        DB::transaction(function () use ($statements): void {
            foreach ($statements as $statement) {
                DB::statement($statement);
            }
        });
    }

    private function normalizePostgreSqlIndexes(): void
    {
        $statements = [
            'ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_name_guard_name_unique',
            'ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_organization_id_name_guard_name_unique',
            'DROP INDEX IF EXISTS roles_name_guard_name_unique',
            'DROP INDEX IF EXISTS roles_organization_id_name_guard_name_unique',

            'DROP INDEX IF EXISTS roles_team_foreign_key_index',
            'DROP INDEX IF EXISTS model_has_roles_team_foreign_key_index',
            'DROP INDEX IF EXISTS model_has_permissions_team_foreign_key_index',

            'CREATE INDEX IF NOT EXISTS roles_organization_scope_index ON roles (organization_id)',
            'CREATE UNIQUE INDEX IF NOT EXISTS roles_global_name_guard_unique ON roles (name, guard_name) WHERE organization_id IS NULL',
            'CREATE UNIQUE INDEX IF NOT EXISTS roles_organization_name_guard_unique ON roles (organization_id, name, guard_name) WHERE organization_id IS NOT NULL',

            'CREATE INDEX IF NOT EXISTS model_has_roles_organization_id_index ON model_has_roles (organization_id)',
            'CREATE INDEX IF NOT EXISTS model_has_permissions_organization_id_index ON model_has_permissions (organization_id)',
        ];

        DB::transaction(function () use ($statements): void {
            foreach ($statements as $statement) {
                DB::statement($statement);
            }
        });
    }

    private function addOrganizationForeignKeys(): void
    {
        $foreignKeyCount = $this->organizationForeignKeyCount();

        if ($foreignKeyCount === 3) {
            return;
        }

        if ($foreignKeyCount !== 0) {
            throw new PermissionMigrationException(
                'Organization permission foreign keys are only partially configured.',
            );
        }

        $statements = [
            'ALTER TABLE roles ADD CONSTRAINT roles_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE',
            'ALTER TABLE model_has_roles ADD CONSTRAINT model_has_roles_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE',
            'ALTER TABLE model_has_permissions ADD CONSTRAINT model_has_permissions_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE',
        ];

        DB::transaction(function () use ($statements): void {
            foreach ($statements as $statement) {
                DB::statement($statement);
            }
        });
    }

    private function assertPostgreSqlSchema(): void
    {
        $teamColumns = $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND table_name IN (
                   'roles',
                   'model_has_roles',
                   'model_has_permissions'
               )
               AND column_name = 'organization_id'",
        );

        $targetIndexes = $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM pg_indexes
             WHERE schemaname = 'public'
               AND indexname IN (
                   'roles_organization_scope_index',
                   'roles_global_name_guard_unique',
                   'roles_organization_name_guard_unique',
                   'model_has_roles_organization_id_index',
                   'model_has_permissions_organization_id_index'
               )",
        );

        $partialIndexes = $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM pg_indexes
             WHERE schemaname = 'public'
               AND indexname IN (
                   'roles_global_name_guard_unique',
                   'roles_organization_name_guard_unique'
               )
               AND indexdef ILIKE '% WHERE %'",
        );

        $oldIndexes = $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM pg_indexes
             WHERE schemaname = 'public'
               AND indexname IN (
                   'roles_name_guard_name_unique',
                   'roles_organization_id_name_guard_name_unique',
                   'roles_team_foreign_key_index',
                   'model_has_roles_team_foreign_key_index',
                   'model_has_permissions_team_foreign_key_index'
               )",
        );

        $organizationPrimaryKeys = $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM pg_constraint
             WHERE contype = 'p'
               AND (
                   (
                       conrelid = 'public.model_has_roles'::regclass
                       AND pg_get_constraintdef(oid) ILIKE
                           '%(organization_id, role_id, model_id, model_type)%'
                   )
                   OR
                   (
                       conrelid = 'public.model_has_permissions'::regclass
                       AND pg_get_constraintdef(oid) ILIKE
                           '%(organization_id, permission_id, model_id, model_type)%'
                   )
               )",
        );

        if (
            $teamColumns !== 3
            || $this->organizationForeignKeyCount() !== 3
            || $targetIndexes !== 5
            || $partialIndexes !== 2
            || $oldIndexes !== 0
            || $organizationPrimaryKeys !== 2
        ) {
            throw new PermissionMigrationException(
                'Organization permission schema verification failed.',
            );
        }
    }

    private function organizationForeignKeyCount(): int
    {
        return $this->scalarInt(
            "SELECT COUNT(*)::int AS aggregate
             FROM pg_constraint
             WHERE contype = 'f'
               AND conname IN (
                   'roles_organization_id_foreign',
                   'model_has_roles_organization_id_foreign',
                   'model_has_permissions_organization_id_foreign'
               )
               AND confrelid = 'public.organizations'::regclass
               AND confdeltype = 'c'",
        );
    }

    private function scalarInt(string $query): int
    {
        $result = DB::selectOne($query);

        return (int) ($result?->aggregate ?? 0);
    }
};
