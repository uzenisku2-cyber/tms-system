<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_settlement_accounting_periods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->date('period_start');
            $table->date('period_end');
            $table->char('currency', 3);
            $table->string('status', 20);
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reopened_at')->nullable();
            $table->text('last_reason');
            $table->unique(['owner_organization_id', 'currency', 'period_start', 'period_end'], 'fsap_period_scope_unique');
            $table->index(['owner_organization_id', 'currency', 'status'], 'fsap_period_scope_status_index');
        });
        Schema::create('financial_settlement_accounting_period_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('accounting_period_id')->constrained('financial_settlement_accounting_periods');
            $table->string('event_type', 64);
            $table->uuid('idempotency_key')->unique();
            $table->char('command_fingerprint', 64);
            $table->json('payload');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestamp('occurred_at');
            $table->unsignedInteger('revision');
            $table->unique(['accounting_period_id', 'revision'], 'fsap_period_events_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlement_accounting_period_events');
        Schema::dropIfExists('financial_settlement_accounting_periods');
    }
};
