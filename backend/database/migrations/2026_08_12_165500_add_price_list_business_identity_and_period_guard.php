<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException(
                sprintf(
                    'Unsupported database driver [%s] for price-list business identity migration.',
                    $driver,
                ),
            );
        }

        Schema::table(
            'price_lists',
            static function (Blueprint $table): void {
                $table
                    ->string('code', 32)
                    ->nullable();
            },
        );

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
UPDATE price_lists
SET code =
    'PL-' ||
    lpad(
        id::text,
        GREATEST(
            6,
            char_length(id::text)
        ),
        '0'
    )
WHERE code IS NULL
SQL
            );
        } else {
            DB::statement(
                <<<'SQL'
UPDATE price_lists
SET code =
    'PL-' ||
    printf('%06d', id)
WHERE code IS NULL
SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    CREATE OR REPLACE FUNCTION drayvia_price_list_code_guard()
    RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    BEGIN
        IF TG_OP = 'INSERT' THEN
            IF NEW.id IS NULL THEN
                RAISE EXCEPTION
                    'Price-list identifier is unavailable.'
                    USING ERRCODE = '23514';
            END IF;

            NEW.code :=
                'PL-' ||
                lpad(
                    NEW.id::text,
                    GREATEST(
                        6,
                        char_length(NEW.id::text)
                    ),
                    '0'
                );

            RETURN NEW;
        END IF;

        IF NEW.code IS DISTINCT FROM OLD.code THEN
            RAISE EXCEPTION
                'Price-list business code is immutable.'
                USING ERRCODE = '23514';
        END IF;

        RETURN NEW;
    END;
    $$
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    CREATE TRIGGER price_lists_business_code_guard
    BEFORE INSERT OR UPDATE OF code
    ON price_lists
    FOR EACH ROW
    EXECUTE FUNCTION drayvia_price_list_code_guard()
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_lists
    ALTER COLUMN code SET NOT NULL
    SQL
            );
        }

        DB::statement(
            <<<'SQL'
CREATE UNIQUE INDEX price_lists_code_unique
ON price_lists (code)
SQL
        );

        Schema::table(
            'price_list_versions',
            static function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger(
                        'organization_relationship_id',
                    )
                    ->nullable();
            },
        );

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
UPDATE price_list_versions AS version
SET organization_relationship_id =
    price_list.organization_relationship_id
FROM price_lists AS price_list
WHERE price_list.id = version.price_list_id
SQL
            );
        } else {
            DB::statement(
                <<<'SQL'
UPDATE price_list_versions
SET organization_relationship_id = (
    SELECT price_list.organization_relationship_id
    FROM price_lists AS price_list
    WHERE price_list.id = price_list_versions.price_list_id
)
SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    CREATE OR REPLACE FUNCTION drayvia_sync_price_list_version_relationship()
    RETURNS trigger
    LANGUAGE plpgsql
    AS $$
    DECLARE
        parent_relationship_id bigint;
    BEGIN
        SELECT organization_relationship_id
        INTO parent_relationship_id
        FROM price_lists
        WHERE id = NEW.price_list_id;

        IF parent_relationship_id IS NULL THEN
            RAISE EXCEPTION
                'Price-list parent relationship is unavailable.'
                USING ERRCODE = '23503';
        END IF;

        IF
            NEW.organization_relationship_id IS NOT NULL
            AND NEW.organization_relationship_id
                <> parent_relationship_id
        THEN
            RAISE EXCEPTION
                'Price-list version relationship must match its parent price list.'
                USING ERRCODE = '23514';
        END IF;

        NEW.organization_relationship_id :=
            parent_relationship_id;

        RETURN NEW;
    END;
    $$
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    CREATE TRIGGER price_list_versions_relationship_sync
    BEFORE INSERT OR UPDATE OF
        price_list_id,
        organization_relationship_id
    ON price_list_versions
    FOR EACH ROW
    EXECUTE FUNCTION drayvia_sync_price_list_version_relationship()
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    ALTER COLUMN organization_relationship_id
    SET NOT NULL
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    ADD CONSTRAINT price_list_versions_relationship_fk
    FOREIGN KEY (organization_relationship_id)
    REFERENCES organization_relationships(id)
    ON DELETE RESTRICT
    SQL
            );
        }

        DB::statement(
            <<<'SQL'
CREATE INDEX price_list_versions_relationship_index
ON price_list_versions (
    organization_relationship_id
)
SQL
        );

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    ADD CONSTRAINT price_list_versions_effective_period_check
    CHECK (
        valid_until IS NULL
        OR (
            valid_from IS NOT NULL
            AND valid_until >= valid_from
        )
    )
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    ADD CONSTRAINT price_list_versions_applicable_start_check
    CHECK (
        status NOT IN (
            'active',
            'replaced',
            'expired'
        )
        OR valid_from IS NOT NULL
    )
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    CREATE EXTENSION IF NOT EXISTS btree_gist
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    ADD CONSTRAINT price_list_versions_relationship_period_exclusion
    EXCLUDE USING gist (
        organization_relationship_id WITH =,
        daterange(
            valid_from,
            valid_until,
            '[]'
        ) WITH &&
    )
    WHERE (
        status IN (
            'active',
            'replaced',
            'expired'
        )
        AND valid_from IS NOT NULL
    )
    SQL
            );
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException(
                sprintf(
                    'Unsupported database driver [%s] for price-list business identity migration rollback.',
                    $driver,
                ),
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    DROP CONSTRAINT IF EXISTS
        price_list_versions_relationship_period_exclusion
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    DROP CONSTRAINT IF EXISTS
        price_list_versions_applicable_start_check
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    DROP CONSTRAINT IF EXISTS
        price_list_versions_effective_period_check
    SQL
            );
        }

        DB::statement(
            <<<'SQL'
DROP INDEX IF EXISTS
    price_list_versions_relationship_index
SQL
        );

        if ($driver === 'pgsql') {
            DB::statement(
                <<<'SQL'
    ALTER TABLE price_list_versions
    DROP CONSTRAINT IF EXISTS
        price_list_versions_relationship_fk
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    DROP TRIGGER IF EXISTS
        price_list_versions_relationship_sync
    ON price_list_versions
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    DROP FUNCTION IF EXISTS
        drayvia_sync_price_list_version_relationship()
    SQL
            );
        }

        Schema::table(
            'price_list_versions',
            static function (Blueprint $table): void {
                $table->dropColumn(
                    'organization_relationship_id',
                );
            },
        );

        DB::statement(
            <<<'SQL'
DROP INDEX IF EXISTS price_lists_code_unique
SQL
        );

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    DROP TRIGGER IF EXISTS
        price_lists_business_code_guard
    ON price_lists
    SQL
            );
        }

        if ($driver === 'pgsql') {
            DB::unprepared(
                <<<'SQL'
    DROP FUNCTION IF EXISTS
        drayvia_price_list_code_guard()
    SQL
            );
        }

        Schema::table(
            'price_lists',
            static function (Blueprint $table): void {
                $table->dropColumn('code');
            },
        );
    }
};
