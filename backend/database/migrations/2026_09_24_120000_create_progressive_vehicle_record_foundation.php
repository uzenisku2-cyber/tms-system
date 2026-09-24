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
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('registration_number')->nullable()->change();
            $table->string('vin')->nullable()->change();
            $table->string('manufacturer')->nullable()->change();
            $table->string('model')->nullable()->change();
        });

        Schema::create('vehicle_record_field_statuses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('organization_context_id')->constrained('organizations')->restrictOnDelete();
            $table->string('field_key', 64);
            $table->string('status', 32);
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['vehicle_id', 'organization_context_id', 'field_key'], 'vehicle_record_field_status_unique');
            $table->index(['organization_context_id', 'status'], 'vehicle_record_field_status_scope_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vehicle_record_field_statuses ADD CONSTRAINT vehicle_record_field_status_values_check CHECK (status IN ('missing','pending_document','unverified','verified','not_applicable'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_record_field_statuses');
        DB::table('vehicles')->whereNull('registration_number')->orderBy('id')->get(['id'])->each(static function (object $vehicle): void {
            DB::table('vehicles')->where('id', $vehicle->id)->update(['registration_number' => 'UNKNOWN-REG-'.$vehicle->id]);
        });
        DB::table('vehicles')->whereNull('vin')->orderBy('id')->get(['id'])->each(static function (object $vehicle): void {
            DB::table('vehicles')->where('id', $vehicle->id)->update(['vin' => 'UNKNOWN-VIN-'.$vehicle->id]);
        });
        DB::table('vehicles')->whereNull('manufacturer')->update(['manufacturer' => 'Unknown']);
        DB::table('vehicles')->whereNull('model')->update(['model' => 'Unknown']);
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('registration_number')->nullable(false)->change();
            $table->string('vin')->nullable(false)->change();
            $table->string('manufacturer')->nullable(false)->change();
            $table->string('model')->nullable(false)->change();
        });
    }
};
