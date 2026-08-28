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
        Schema::create('fuel_import_row_finalizations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_import_row_id')->unique()->constrained('fuel_import_rows')->restrictOnDelete();
            $table->foreignId('fuel_import_row_correction_id')->constrained('fuel_import_row_corrections')->restrictOnDelete();
            $table->foreignId('fuel_transaction_id')->constrained('fuel_transactions')->restrictOnDelete();
            $table->unsignedInteger('correction_revision');
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->jsonb('before_snapshot')->nullable();
            $table->jsonb('after_snapshot');
            $table->text('reason');
            $table->foreignId('finalized_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at');
            $table->timestamps();
            $table->index(['fuel_transaction_id', 'finalized_at'], 'fuel_row_finalizations_transaction_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_import_row_finalizations ADD CONSTRAINT fuel_import_row_finalizations_status_check CHECK (from_status IN ('review','rejected') AND to_status = 'accepted' AND correction_revision > 0)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_import_row_finalizations');
    }
};
