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
        Schema::create('fuel_transaction_reconciliations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('fuel_transaction_id')->unique()->constrained('fuel_transactions')->restrictOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('result_code', 64)->nullable();
            $table->foreignId('effective_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $table->foreignId('driver_organization_assignment_id')->nullable()->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('candidate_count')->default(0);
            $table->foreignId('matched_daily_report_id')->nullable()->constrained('daily_reports')->restrictOnDelete();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['owner_organization_id', 'status', 'service_date'], 'fuel_reconciliations_owner_status_date_index');
            $table->index(['effective_driver_id', 'service_date'], 'fuel_reconciliations_driver_date_index');
        });

        Schema::create('fuel_transaction_reconciliation_evaluations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_transaction_reconciliation_id')->constrained('fuel_transaction_reconciliations')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('evaluation_version', 32);
            $table->string('result_code', 64);
            $table->foreignId('effective_driver_id')->nullable()->constrained('drivers')->restrictOnDelete();
            $table->foreignId('driver_organization_assignment_id')->nullable()->constrained('driver_organization_assignments')->restrictOnDelete();
            $table->unsignedInteger('candidate_count')->default(0);
            $table->foreignId('matched_daily_report_id')->nullable()->constrained('daily_reports')->restrictOnDelete();
            $table->json('evidence');
            $table->foreignId('evaluated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('evaluated_at');
            $table->timestamps();
            $table->unique(['fuel_transaction_reconciliation_id', 'revision'], 'fuel_reconciliation_evaluations_revision_unique');
        });

        Schema::create('fuel_transaction_reconciliation_decisions', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_transaction_reconciliation_id')->constrained('fuel_transaction_reconciliations')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('previous_status', 32);
            $table->string('new_status', 32);
            $table->string('decision_code', 64);
            $table->foreignId('selected_daily_report_id')->nullable()->constrained('daily_reports')->restrictOnDelete();
            $table->text('reason');
            $table->foreignId('decided_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->unique(['fuel_transaction_reconciliation_id', 'revision'], 'fuel_reconciliation_decisions_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fuel_transaction_reconciliations ADD CONSTRAINT fuel_reconciliations_status_check CHECK (status IN ('pending','matched','review_required','resolved') AND revision >= 0)");
            DB::statement("ALTER TABLE fuel_transaction_reconciliation_decisions ADD CONSTRAINT fuel_reconciliation_decisions_code_check CHECK (decision_code IN ('confirm_driver_day','select_daily_report','accept_without_operational_activity','return_to_review'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transaction_reconciliation_decisions');
        Schema::dropIfExists('fuel_transaction_reconciliation_evaluations');
        Schema::dropIfExists('fuel_transaction_reconciliations');
    }
};
