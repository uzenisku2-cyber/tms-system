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
        Schema::create('vehicle_compliance_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('record_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('compliance_type', 48);
            $table->string('identifier')->nullable();
            $table->date('inspected_at')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 32);
            $table->string('result', 32)->nullable();
            $table->unsignedBigInteger('odometer')->nullable();
            $table->string('issuer_name')->nullable();
            $table->foreignId('primary_document_id')->nullable()->constrained('vehicle_documents')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['record_uid', 'revision'], 'vehicle_compliance_record_revision_unique');
            $table->index(['vehicle_id', 'compliance_type', 'valid_until', 'status'], 'vehicle_compliance_due_index');
        });

        Schema::create('vehicle_insurance_policies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('record_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('policy_type', 48);
            $table->string('insurer_name');
            $table->string('policy_number');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 32);
            $table->decimal('coverage_amount', 14, 2)->nullable();
            $table->decimal('deductible_amount', 14, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->foreignId('primary_document_id')->nullable()->constrained('vehicle_documents')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['record_uid', 'revision'], 'vehicle_insurance_policy_revision_unique');
            $table->index(['vehicle_id', 'policy_type', 'valid_until', 'status'], 'vehicle_insurance_due_index');
        });

        Schema::create('vehicle_service_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('record_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('service_type', 48);
            $table->string('status', 32);
            $table->string('summary');
            $table->text('details')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('completed_at')->nullable();
            $table->date('next_service_on')->nullable();
            $table->unsignedBigInteger('odometer')->nullable();
            $table->unsignedBigInteger('next_service_odometer')->nullable();
            $table->foreignId('provider_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->string('external_provider_name')->nullable();
            $table->foreignId('primary_document_id')->nullable()->constrained('vehicle_documents')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamps();
            $table->unique(['record_uid', 'revision'], 'vehicle_service_record_revision_unique');
            $table->index(['vehicle_id', 'status', 'next_service_on'], 'vehicle_service_due_index');
        });

        Schema::create('vehicle_incidents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('record_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('incident_type', 48);
            $table->timestamp('occurred_at');
            $table->timestamp('reported_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('status', 32);
            $table->string('severity', 32);
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('responsible_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->string('location')->nullable();
            $table->string('police_reference')->nullable();
            $table->string('insurance_claim_reference')->nullable();
            $table->foreignId('primary_document_id')->nullable()->constrained('vehicle_documents')->restrictOnDelete();
            $table->text('description');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamps();
            $table->unique(['record_uid', 'revision'], 'vehicle_incident_revision_unique');
            $table->index(['vehicle_id', 'status', 'occurred_at'], 'vehicle_incident_status_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_compliance_records ADD CONSTRAINT vehicle_compliance_values_check CHECK (compliance_type IN ('technical_inspection','emissions','registration','roadworthiness','other') AND status IN ('pending','valid','expired','failed','waived') AND (result IS NULL OR result IN ('passed','failed','conditional','not_applicable')) AND revision >= 1 AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE vehicle_insurance_policies ADD CONSTRAINT vehicle_insurance_values_check CHECK (policy_type IN ('compulsory_liability','casco','gap','assistance','other') AND status IN ('pending','active','expired','cancelled') AND revision >= 1 AND (valid_until IS NULL OR valid_until >= valid_from) AND (coverage_amount IS NULL OR coverage_amount >= 0) AND (deductible_amount IS NULL OR deductible_amount >= 0))");
            DB::statement("ALTER TABLE vehicle_service_records ADD CONSTRAINT vehicle_service_values_check CHECK (service_type IN ('scheduled','repair','inspection','tyres','recall','other') AND status IN ('planned','in_progress','completed','cancelled') AND revision >= 1 AND (completed_at IS NULL OR completed_at >= opened_at) AND (next_service_odometer IS NULL OR odometer IS NULL OR next_service_odometer >= odometer))");
            DB::statement("ALTER TABLE vehicle_incidents ADD CONSTRAINT vehicle_incident_values_check CHECK (incident_type IN ('accident','damage','theft','vandalism','breakdown','other') AND status IN ('reported','investigating','repair_in_progress','resolved','closed','rejected') AND severity IN ('minor','major','critical','total_loss') AND revision >= 1 AND reported_at >= occurred_at AND (resolved_at IS NULL OR resolved_at >= occurred_at))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_incidents');
        Schema::dropIfExists('vehicle_service_records');
        Schema::dropIfExists('vehicle_insurance_policies');
        Schema::dropIfExists('vehicle_compliance_records');
    }
};
