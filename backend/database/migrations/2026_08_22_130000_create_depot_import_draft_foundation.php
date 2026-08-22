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
                    'Unsupported database driver [%s] for depot-import draft foundation migration.',
                    $driver,
                ),
            );
        }

        Schema::create(
            'depot_import_batches',
            static function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->foreignId('created_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->string('status', 32)->default('draft');
                $table->unsignedInteger('lock_version')->default(1);
                $table->string('original_filename', 255);
                $table->char('source_sha256', 64);
                $table->char('schema_fingerprint', 64);
                $table->string('sheet_name', 255);
                $table->unsignedInteger('header_start_row');
                $table->unsignedInteger('header_end_row');
                $table->unsignedInteger('data_start_row');
                $table->string('confirmed_carrier_alias', 255);
                $table->string('confirmed_carrier_alias_normalized', 255);
                $table->date('period_from');
                $table->date('period_until');
                $table->unsignedInteger('row_count');
                $table->unsignedInteger('ready_row_count');
                $table->unsignedInteger('no_run_row_count');
                $table->unsignedInteger('excluded_carrier_row_count');
                $table->unsignedInteger('source_driver_count');
                $table->unsignedInteger('unassigned_ready_row_count');
                $table->jsonb('source_totals');
                $table->char('protected_totals_sha256', 64);
                $table->timestamps();

                $table->unique(
                    [
                        'organization_id',
                        'source_sha256',
                        'confirmed_carrier_alias_normalized',
                    ],
                    'depot_import_batches_source_alias_unique',
                );

                $table->index(
                    ['organization_id', 'status', 'created_at'],
                    'depot_import_batches_org_status_created_index',
                );
            },
        );

        Schema::create(
            'depot_import_rows',
            static function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                $table->foreignId('depot_import_batch_id')
                    ->constrained('depot_import_batches')
                    ->restrictOnDelete();

                $table->unsignedInteger('source_row');
                $table->string('status', 32);
                $table->date('service_date');
                $table->string('route_number', 100);
                $table->string('route_number_normalized', 100);
                $table->string('carrier_name', 255);
                $table->string('source_driver_name', 255);
                $table->string('source_driver_key', 255);

                $table->foreignId('assigned_driver_id')
                    ->nullable()
                    ->constrained('drivers')
                    ->restrictOnDelete();

                $table->foreignId(
                    'assigned_driver_organization_assignment_id',
                )
                    ->nullable()
                    ->constrained('driver_organization_assignments')
                    ->restrictOnDelete();

                $table->time('departure_time')->nullable();
                $table->time('arrival_time')->nullable();
                $table->decimal('actual_km', 10, 2)->nullable();
                $table->decimal('planned_km', 10, 2)->nullable();
                $table->unsignedInteger('loaded_parcels')->nullable();
                $table->unsignedInteger('delivered_parcels')->nullable();
                $table->unsignedInteger('redirected_parcels')->nullable();
                $table->unsignedInteger('customer_rejected_parcels')->nullable();
                $table->unsignedInteger('reported_not_delivered_parcels')->nullable();
                $table->unsignedInteger('computed_not_delivered_parcels')->nullable();
                $table->decimal('surcharge_amount', 10, 2)->nullable();
                $table->text('operational_notes')->nullable();
                $table->jsonb('errors');
                $table->jsonb('warnings');
                $table->char('protected_values_sha256', 64);
                $table->timestamps();

                $table->unique(
                    ['depot_import_batch_id', 'source_row'],
                    'depot_import_rows_batch_source_row_unique',
                );

                $table->index(
                    [
                        'depot_import_batch_id',
                        'source_driver_key',
                        'status',
                    ],
                    'depot_import_rows_batch_driver_status_index',
                );

                $table->index(
                    [
                        'depot_import_batch_id',
                        'service_date',
                        'route_number_normalized',
                    ],
                    'depot_import_rows_batch_route_index',
                );
            },
        );

        Schema::create(
            'depot_import_events',
            static function (Blueprint $table): void {
                $table->id();

                $table->foreignId('depot_import_batch_id')
                    ->constrained('depot_import_batches')
                    ->restrictOnDelete();

                $table->foreignId('depot_import_row_id')
                    ->nullable()
                    ->constrained('depot_import_rows')
                    ->restrictOnDelete();

                $table->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->string('event_type', 64);

                $table->foreignId('acted_by_user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->text('reason')->nullable();
                $table->jsonb('before_payload')->nullable();
                $table->jsonb('after_payload')->nullable();
                $table->char('protected_totals_sha256_before', 64);
                $table->char('protected_totals_sha256_after', 64);
                $table->timestamp('created_at')->useCurrent();

                $table->index(
                    ['depot_import_batch_id', 'created_at'],
                    'depot_import_events_batch_created_index',
                );

                $table->index(
                    ['organization_id', 'event_type', 'created_at'],
                    'depot_import_events_org_type_created_index',
                );
            },
        );

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_batches
ADD CONSTRAINT depot_import_batches_status_check
CHECK (status IN ('draft', 'ready', 'imported', 'cancelled'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_batches
ADD CONSTRAINT depot_import_batches_period_check
CHECK (period_from <= period_until)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_batches
ADD CONSTRAINT depot_import_batches_counts_check
CHECK (
    lock_version >= 1
    AND row_count >= 1
    AND ready_row_count >= 1
    AND row_count = ready_row_count + no_run_row_count
    AND unassigned_ready_row_count <= ready_row_count
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_batches
ADD CONSTRAINT depot_import_batches_hashes_check
CHECK (
    source_sha256 ~ '^[0-9A-F]{64}$'
    AND schema_fingerprint ~ '^[0-9A-F]{64}$'
    AND protected_totals_sha256 ~ '^[0-9A-F]{64}$'
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_rows
ADD CONSTRAINT depot_import_rows_status_check
CHECK (status IN ('ready', 'no_run'))
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_rows
ADD CONSTRAINT depot_import_rows_assignment_check
CHECK (
    (assigned_driver_id IS NULL) =
    (assigned_driver_organization_assignment_id IS NULL)
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_rows
ADD CONSTRAINT depot_import_rows_hash_check
CHECK (protected_values_sha256 ~ '^[0-9A-F]{64}$')
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_events
ADD CONSTRAINT depot_import_events_type_check
CHECK (
    event_type IN (
        'draft_created',
        'source_driver_mapped',
        'import_finalized',
        'import_cancelled'
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE depot_import_events
ADD CONSTRAINT depot_import_events_hashes_check
CHECK (
    protected_totals_sha256_before ~ '^[0-9A-F]{64}$'
    AND protected_totals_sha256_after ~ '^[0-9A-F]{64}$'
)
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_import_events');
        Schema::dropIfExists('depot_import_rows');
        Schema::dropIfExists('depot_import_batches');
    }
};
