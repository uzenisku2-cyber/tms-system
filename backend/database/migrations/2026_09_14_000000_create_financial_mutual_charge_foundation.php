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
        Schema::create('financial_mutual_charges', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')->constrained('organizations');
            $table->foreignId('counterparty_organization_id')->nullable()->constrained('organizations');
            $table->foreignId('counterparty_driver_id')->nullable()->constrained('drivers');
            $table->string('counterparty_type', 32);
            $table->string('direction', 16);
            $table->string('category', 32);
            $table->string('description', 500);
            $table->date('service_period_from');
            $table->date('service_period_until');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('vat_treatment', 32);
            $table->boolean('offset_eligible')->default(true);
            $table->string('status', 24)->default('draft');
            $table->string('visibility_status', 16)->default('private');
            $table->string('source_type', 100);
            $table->uuid('source_public_id');
            $table->json('source_snapshot');
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_organization_id', 'idempotency_key'], 'financial_mutual_charges_owner_idempotency_unique');
            $table->unique(['owner_organization_id', 'source_type', 'source_public_id', 'direction'], 'financial_mutual_charges_source_direction_unique');
            $table->index(['owner_organization_id', 'status', 'service_period_from'], 'financial_mutual_charges_owner_status_period_index');
            $table->index(['counterparty_organization_id', 'visibility_status'], 'financial_mutual_charges_counterparty_org_visibility_index');
            $table->index(['counterparty_driver_id', 'visibility_status'], 'financial_mutual_charges_counterparty_driver_visibility_index');
        });

        Schema::create('financial_mutual_charge_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('financial_mutual_charge_id')->constrained('financial_mutual_charges');
            $table->unsignedInteger('revision');
            $table->string('event_type', 32);
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('reason', 1000);
            $table->json('evidence');
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestamp('occurred_at');

            $table->unique(['financial_mutual_charge_id', 'revision'], 'financial_mutual_charge_events_charge_revision_unique');
            $table->unique(['financial_mutual_charge_id', 'idempotency_key'], 'financial_mutual_charge_events_charge_idempotency_unique');
        });

        $driverPartyCheck = "counterparty_type <> 'driver' OR (counterparty_driver_id IS NOT NULL AND counterparty_organization_id IS NULL)";
        $organizationPartyCheck = "counterparty_type <> 'organization' OR (counterparty_organization_id IS NOT NULL AND counterparty_driver_id IS NULL)";
        $periodCheck = 'service_period_until >= service_period_from';
        $amountCheck = 'amount_minor > 0';

        if (DB::getDriverName() !== 'sqlite') {
            foreach ([
                'financial_mutual_charges_driver_party_check' => $driverPartyCheck,
                'financial_mutual_charges_organization_party_check' => $organizationPartyCheck,
                'financial_mutual_charges_period_check' => $periodCheck,
                'financial_mutual_charges_amount_check' => $amountCheck,
            ] as $name => $expression) {
                DB::statement("ALTER TABLE financial_mutual_charges ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_mutual_charge_events');
        Schema::dropIfExists('financial_mutual_charges');
    }
};
