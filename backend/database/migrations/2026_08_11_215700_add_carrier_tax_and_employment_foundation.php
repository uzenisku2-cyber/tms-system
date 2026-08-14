<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('vat_status', 32)
                ->nullable()
                ->after('vat_number');

            $table->timestamp('ares_verified_at')
                ->nullable()
                ->after('vat_status');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE organizations
             ADD CONSTRAINT organizations_vat_status_check
             CHECK (
                 vat_status IS NULL
                 OR vat_status IN ('payer', 'non_payer')
             )"
            );
        }

        DB::statement(
            'CREATE UNIQUE INDEX
             drayvia_organizations_registration_number_unique
             ON organizations (registration_number)
             WHERE registration_number IS NOT NULL'
        );

        Schema::table(
            'driver_organization_assignments',
            function (Blueprint $table): void {
                $table->string('employment_type', 32)
                    ->nullable()
                    ->after('organization_id');
            },
        );

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE driver_organization_assignments
             ADD CONSTRAINT driver_org_assignment_employment_type_check
             CHECK (
                 employment_type IS NULL
                 OR employment_type IN (
                     'employee',
                     'dpp',
                     'dpc',
                     'other'
                 )
             )"
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE driver_organization_assignments
             DROP CONSTRAINT IF EXISTS
             driver_org_assignment_employment_type_check'
            );
        }

        Schema::table(
            'driver_organization_assignments',
            function (Blueprint $table): void {
                $table->dropColumn('employment_type');
            },
        );

        DB::statement(
            'DROP INDEX IF EXISTS
             drayvia_organizations_registration_number_unique'
        );

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE organizations
             DROP CONSTRAINT IF EXISTS organizations_vat_status_check'
            );
        }

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'vat_status',
                'ares_verified_at',
            ]);
        });
    }
};
