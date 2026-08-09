<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'driver_organization_assignments',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('driver_id')
                    ->constrained('drivers')
                    ->restrictOnDelete();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->date('valid_from');
                $table->date('valid_until')->nullable();

                $table->string('end_reason', 1000)
                    ->nullable();

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId('ended_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'driver_id',
                        'valid_from',
                    ],
                    'driver_assignment_driver_from_index',
                );

                $table->index(
                    [
                        'organization_id',
                        'valid_from',
                    ],
                    'driver_assignment_org_from_index',
                );
            },
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE driver_organization_assignments
ADD CONSTRAINT driver_assignment_period_check
CHECK (
    valid_until IS NULL
    OR valid_until >= valid_from
)
SQL);

            DB::statement(<<<'SQL'
CREATE UNIQUE INDEX driver_assignment_one_open_unique
ON driver_organization_assignments (driver_id)
WHERE valid_until IS NULL
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'driver_organization_assignments',
        );
    }
};