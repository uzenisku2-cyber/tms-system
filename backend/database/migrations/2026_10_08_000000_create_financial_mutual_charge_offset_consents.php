<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_mutual_charge_offset_consents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('financial_mutual_charge_id')->constrained('financial_mutual_charges');
            $table->unsignedInteger('charge_revision');
            $table->char('charge_fingerprint', 64);
            $table->string('decision', 16);
            $table->foreignId('actor_user_id')->constrained('users');
            $table->foreignId('acting_organization_id')->constrained('organizations');
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('reason', 1000);
            $table->timestamp('decided_at');
            $table->unique(['financial_mutual_charge_id', 'idempotency_key'], 'mutual_charge_offset_consent_idempotency');
            $table->index(['financial_mutual_charge_id', 'charge_revision'], 'mutual_charge_offset_consent_revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_mutual_charge_offset_consents');
    }
};
