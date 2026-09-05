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
        Schema::create('vehicle_cost_allocations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('allocation_uid');
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('source_type', 32);
            $table->uuid('source_reference_uid')->nullable();
            $table->string('source_document_reference')->nullable();
            $table->date('occurred_on');
            $table->text('description');
            $table->decimal('net_amount', 14, 2);
            $table->decimal('vat_amount', 14, 2);
            $table->decimal('gross_amount', 14, 2);
            $table->char('currency', 3);
            $table->string('status', 24);
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['allocation_uid', 'revision'], 'vehicle_cost_allocation_revision_unique');
            $table->index(['vehicle_id', 'occurred_on', 'status'], 'vehicle_cost_allocation_operational_index');
        });

        Schema::create('vehicle_cost_allocation_lines', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('line_uid');
            $table->foreignId('vehicle_cost_allocation_id')->constrained('vehicle_cost_allocations')->restrictOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->string('cost_component', 32);
            $table->string('responsible_party_type', 24);
            $table->foreignId('responsible_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('external_party_name')->nullable();
            $table->decimal('net_amount', 14, 2);
            $table->decimal('vat_amount', 14, 2);
            $table->decimal('gross_amount', 14, 2);
            $table->char('currency', 3);
            $table->string('settlement_mode', 32);
            $table->string('vat_treatment', 32);
            $table->unsignedInteger('vat_rate_basis_points')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['line_uid', 'revision'], 'vehicle_cost_allocation_line_revision_unique');
            $table->unique(['vehicle_cost_allocation_id', 'sequence_number', 'revision'], 'vehicle_cost_allocation_sequence_revision_unique');
            $table->index(['responsible_party_type', 'responsible_organization_id', 'responsible_user_id'], 'vehicle_cost_responsible_party_index');
        });

        Schema::create('vehicle_cost_allocation_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_cost_allocation_id')->constrained('vehicle_cost_allocations')->restrictOnDelete();
            $table->string('event_type', 32);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->json('evidence')->nullable();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamp('occurred_at');
            $table->unique(['vehicle_cost_allocation_id', 'revision'], 'vehicle_cost_allocation_event_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_cost_allocations ADD CONSTRAINT vehicle_cost_allocation_values_check CHECK (source_type IN ('service','incident','insurance','provision','rental','leasing','installment','manual','other') AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND status IN ('draft','approved','superseded','cancelled') AND revision >= 1 AND ((approved_by_user_id IS NULL AND approved_at IS NULL) OR (approved_by_user_id IS NOT NULL AND approved_at IS NOT NULL)))");
            DB::statement("ALTER TABLE vehicle_cost_allocation_lines ADD CONSTRAINT vehicle_cost_allocation_line_values_check CHECK (sequence_number >= 1 AND cost_component IN ('base_cost','vat','deductible','damage','rental','leasing','installment','insurance_recovery','other') AND responsible_party_type IN ('organization','driver','insurer','state','internal','external_party') AND ((responsible_party_type = 'organization' AND responsible_organization_id IS NOT NULL AND responsible_user_id IS NULL AND external_party_name IS NULL) OR (responsible_party_type = 'driver' AND responsible_user_id IS NOT NULL AND responsible_organization_id IS NULL AND external_party_name IS NULL) OR (responsible_party_type IN ('insurer','external_party') AND external_party_name IS NOT NULL AND responsible_organization_id IS NULL AND responsible_user_id IS NULL) OR (responsible_party_type IN ('state','internal') AND responsible_organization_id IS NULL AND responsible_user_id IS NULL AND external_party_name IS NULL)) AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND settlement_mode IN ('invoice_required','deposit_offset','repair_fund_reserve','insurance_recovery','state_recovery','informational_only','manual_review') AND vat_treatment IN ('standard_rate','outside_scope','not_applicable','pending_review') AND (vat_rate_basis_points IS NULL OR vat_rate_basis_points <= 10000) AND revision >= 1)");
            DB::statement("ALTER TABLE vehicle_cost_allocation_events ADD CONSTRAINT vehicle_cost_allocation_event_values_check CHECK (event_type IN ('created','approved','superseded','cancelled') AND to_status IN ('draft','approved','superseded','cancelled') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_cost_allocation_events');
        Schema::dropIfExists('vehicle_cost_allocation_lines');
        Schema::dropIfExists('vehicle_cost_allocations');
    }
};
