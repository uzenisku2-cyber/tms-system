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
        Schema::create('fuel_transaction_export_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('exported_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('format', 16)->default('csv');
            $table->string('filename');
            $table->json('filters');
            $table->unsignedInteger('row_count');
            $table->timestamp('exported_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organization_id', 'exported_at'], 'fuel_export_events_org_exported_index');
            $table->index(['organization_id', 'exported_by_user_id'], 'fuel_export_events_org_actor_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_transaction_export_events ADD CONSTRAINT fuel_export_events_format_check CHECK (format = 'csv')");
            DB::statement('ALTER TABLE fuel_transaction_export_events ADD CONSTRAINT fuel_export_events_row_count_check CHECK (row_count >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transaction_export_events');
    }
};
