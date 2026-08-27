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
        Schema::create('fuel_import_batches', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->string('provider', 32);
            $t->string('status', 40);
            $t->string('original_filename');
            $t->char('file_sha256', 64);
            $t->char('schema_fingerprint', 64);
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->unsignedInteger('source_row_count')->default(0);
            $t->unsignedInteger('accepted_row_count')->default(0);
            $t->unsignedInteger('duplicate_row_count')->default(0);
            $t->unsignedInteger('review_row_count')->default(0);
            $t->unsignedInteger('rejected_row_count')->default(0);
            $t->foreignId('imported_by_user_id')->constrained('users')->restrictOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['owner_organization_id', 'provider', 'file_sha256'], 'fuel_import_batches_source_unique');
            $t->index(['owner_organization_id', 'provider', 'created_at'], 'fuel_import_batches_owner_provider_index');
        });
        Schema::create('fuel_transactions', static function (Blueprint $t): void {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $t->string('provider', 32);
            $t->string('provider_transaction_identifier', 191)->nullable();
            $t->char('transaction_fingerprint', 64);
            $t->timestamp('occurred_at');
            $t->date('posting_date')->nullable();
            $t->string('provider_card_identifier', 128);
            $t->foreignId('fuel_card_id')->nullable()->constrained('fuel_cards')->restrictOnDelete();
            $t->foreignId('fuel_card_assignment_id')->nullable()->constrained('fuel_card_assignments')->restrictOnDelete();
            $t->foreignId('responsible_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $t->string('match_status', 32);
            $t->string('match_method', 64)->nullable();
            $t->string('station_identifier', 128)->nullable();
            $t->string('station_name')->nullable();
            $t->string('station_address')->nullable();
            $t->string('product_code', 64)->nullable();
            $t->string('product_name')->nullable();
            $t->decimal('quantity', 18, 6);
            $t->string('unit_of_measure', 16);
            $t->decimal('unit_price', 18, 6)->nullable();
            $t->decimal('net_amount', 20, 6)->nullable();
            $t->decimal('tax_amount', 20, 6)->nullable();
            $t->decimal('gross_amount', 20, 6);
            $t->decimal('discount_amount', 20, 6)->nullable();
            $t->decimal('tax_rate', 9, 4)->nullable();
            $t->char('currency', 3);
            $t->string('vehicle_registration', 32)->nullable();
            $t->decimal('odometer', 16, 2)->nullable();
            $t->string('invoice_reference', 128)->nullable();
            $t->text('source_description')->nullable();
            $t->foreignId('fuel_import_batch_id')->constrained('fuel_import_batches')->restrictOnDelete();
            $t->unsignedInteger('source_row');
            $t->timestamps();
            $t->unique(['owner_organization_id', 'provider', 'transaction_fingerprint'], 'fuel_transactions_fingerprint_unique');
            $t->index(['fuel_card_id', 'occurred_at'], 'fuel_transactions_card_time_index');
        });
        Schema::create('fuel_import_rows', static function (Blueprint $t): void {
            $t->id();
            $t->foreignId('fuel_import_batch_id')->constrained('fuel_import_batches')->cascadeOnDelete();
            $t->unsignedInteger('source_row');
            $t->string('status', 32);
            $t->char('row_fingerprint', 64);
            $t->string('provider_transaction_identifier', 191)->nullable();
            $t->jsonb('raw_payload');
            $t->jsonb('normalized_payload')->nullable();
            $t->jsonb('validation_messages')->nullable();
            $t->foreignId('fuel_transaction_id')->nullable()->constrained('fuel_transactions')->restrictOnDelete();
            $t->foreignId('duplicate_fuel_transaction_id')->nullable()->constrained('fuel_transactions')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['fuel_import_batch_id', 'source_row'], 'fuel_import_rows_batch_row_unique');
            $t->index(['fuel_import_batch_id', 'status'], 'fuel_import_rows_batch_status_index');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_import_batches ADD CONSTRAINT fuel_import_batches_values_check CHECK (provider IN ('ORLEN','MOL') AND status IN ('processing','completed','completed_with_review','failed'))");
            DB::statement("ALTER TABLE fuel_transactions ADD CONSTRAINT fuel_transactions_values_check CHECK (provider IN ('ORLEN','MOL') AND match_status IN ('matched','review') AND quantity > 0 AND gross_amount >= 0 AND currency ~ '^[A-Z]{3}$')");
            DB::statement("ALTER TABLE fuel_import_rows ADD CONSTRAINT fuel_import_rows_values_check CHECK (status IN ('accepted','duplicate','review','rejected'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_import_rows');
        Schema::dropIfExists('fuel_transactions');
        Schema::dropIfExists('fuel_import_batches');
    }
};
