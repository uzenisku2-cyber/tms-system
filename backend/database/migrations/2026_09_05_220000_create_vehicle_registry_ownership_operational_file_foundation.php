<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique();
            $table->date('first_registered_on')->nullable();
            $table->string('odometer_unit', 8)->default('km');
            $table->string('lifecycle_status', 32)->default('active');
            $table->unsignedInteger('current_revision')->default(1);
            $table->timestamp('archived_at')->nullable();
            $table->index(['lifecycle_status', 'active'], 'vehicles_lifecycle_active_index');
        });

        DB::table('vehicles')->whereNull('public_id')->orderBy('id')->pluck('id')->each(
            static fn (mixed $vehicleId): int => DB::table('vehicles')->where('id', (int) $vehicleId)->update(['public_id' => (string) Str::uuid()])
        );

        Schema::create('vehicle_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('owner_type', 32);
            $table->foreignId('owner_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('external_owner_name')->nullable();
            $table->unsignedSmallInteger('ownership_share_basis_points')->default(10000);
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->string('acquisition_basis', 64)->nullable();
            $table->string('verification_status', 32)->default('unverified');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('change_reason');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['vehicle_id', 'valid_from', 'valid_until'], 'vehicle_ownership_effective_index');
        });

        Schema::create('vehicle_responsibilities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('responsibility_type', 48);
            $table->string('party_type', 32);
            $table->foreignId('party_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('party_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('external_party_name')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->string('source', 64);
            $table->string('status', 32)->default('active');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['vehicle_id', 'responsibility_type', 'valid_from', 'valid_until'], 'vehicle_responsibility_effective_index');
        });

        Schema::create('vehicle_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('document_type', 64);
            $table->string('title');
            $table->text('storage_reference');
            $table->date('issue_date')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('verification_status', 32)->default('unverified');
            $table->string('access_classification', 32)->default('operational');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['vehicle_id', 'document_type', 'valid_until'], 'vehicle_document_validity_index');
        });

        Schema::create('vehicle_registry_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('event_type', 64);
            $table->unsignedInteger('vehicle_revision');
            $table->text('reason');
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->unique(['vehicle_id', 'vehicle_revision'], 'vehicle_registry_event_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_ownerships ADD CONSTRAINT vehicle_ownership_values_check CHECK (owner_type IN ('organization','user','external_party','financing_provider') AND ownership_share_basis_points BETWEEN 1 AND 10000 AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE vehicle_responsibilities ADD CONSTRAINT vehicle_responsibility_values_check CHECK (responsibility_type IN ('registered_operator','operational_organization','custodian','authorized_user','default_driver') AND party_type IN ('organization','user','external_party') AND status IN ('active','ended','cancelled') AND (valid_until IS NULL OR valid_until >= valid_from))");
            DB::statement("ALTER TABLE vehicle_documents ADD CONSTRAINT vehicle_document_values_check CHECK (verification_status IN ('unverified','verified','rejected','expired') AND access_classification IN ('operational','restricted','financial','private') AND (valid_until IS NULL OR valid_from IS NULL OR valid_until >= valid_from))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_registry_events');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('vehicle_responsibilities');
        Schema::dropIfExists('vehicle_ownerships');
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropIndex('vehicles_lifecycle_active_index');
            $table->dropColumn(['public_id', 'first_registered_on', 'odometer_unit', 'lifecycle_status', 'current_revision', 'archived_at']);
        });
    }
};
