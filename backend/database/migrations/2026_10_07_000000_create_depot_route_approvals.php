<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depot_route_approvals', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('daily_report_id')->constrained('daily_reports')->restrictOnDelete();
            $table->unsignedInteger('daily_report_version');
            $table->foreignId('depot_import_batch_id')->constrained('depot_import_batches')->restrictOnDelete();
            $table->foreignId('depot_import_row_id')->constrained('depot_import_rows')->restrictOnDelete();
            $table->char('depot_values_sha256', 64);
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamp('approved_at');
            $table->timestamps();
            $table->unique(['daily_report_id', 'daily_report_version', 'depot_import_row_id'], 'depot_route_approvals_source_unique');
            $table->index(['organization_id', 'depot_import_batch_id'], 'depot_route_approvals_batch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_route_approvals');
    }
};
