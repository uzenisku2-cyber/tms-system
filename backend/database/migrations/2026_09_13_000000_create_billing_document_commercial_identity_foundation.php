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
        Schema::create('billing_document_commercial_identities', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('billing_document_id')->unique()->constrained('billing_documents')->restrictOnDelete();
            $table->foreignId('owner_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->char('command_fingerprint', 64);
            $table->string('direction', 16);
            $table->string('document_number', 64);
            $table->string('variable_symbol', 32)->nullable();
            $table->date('issued_on');
            $table->date('taxable_supply_on')->nullable();
            $table->date('due_on');
            $table->string('counterparty_name', 255);
            $table->string('counterparty_registration_number', 32)->nullable();
            $table->string('counterparty_vat_number', 32)->nullable();
            $table->string('counterparty_account_identifier', 128)->nullable();
            $table->json('counterparty_snapshot');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['owner_organization_id', 'idempotency_key'], 'billing_document_identity_org_idempotency_unique');
            $table->unique(['owner_organization_id', 'document_number'], 'billing_document_identity_org_number_unique');
            $table->index(['direction', 'due_on'], 'billing_document_identity_direction_due_index');
        });

        Schema::create('billing_document_commercial_identity_events', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('billing_document_commercial_identity_id')->constrained('billing_document_commercial_identities')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('event_type', 40);
            $table->text('reason');
            $table->json('evidence');
            $table->timestamp('occurred_at');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->unique(['billing_document_commercial_identity_id', 'revision'], 'billing_document_identity_event_revision_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE billing_documents DROP CONSTRAINT billing_documents_values_check');
            DB::statement("ALTER TABLE billing_documents ADD CONSTRAINT billing_documents_values_check CHECK (document_type IN ('customer_invoice','external_carrier_settlement','driver_remuneration','supplier_fuel_invoice') AND status IN ('draft','under_review','approved','closed','cancelled') AND vat_treatment IN ('standard','not_applicable') AND vat_status_snapshot IN ('payer','non_payer') AND period_until >= period_from AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND ((vat_treatment = 'standard' AND vat_status_snapshot = 'payer' AND vat_rate IS NOT NULL) OR (vat_treatment = 'not_applicable' AND vat_status_snapshot = 'non_payer' AND vat_rate IS NULL AND vat_amount = 0)) AND ((document_type IN ('customer_invoice','external_carrier_settlement') AND counterparty_organization_id IS NOT NULL AND driver_id IS NULL) OR (document_type = 'driver_remuneration' AND counterparty_organization_id IS NULL AND driver_id IS NOT NULL AND vat_treatment = 'not_applicable') OR (document_type = 'supplier_fuel_invoice' AND counterparty_organization_id IS NULL AND driver_id IS NULL)))");
            DB::statement("ALTER TABLE billing_document_commercial_identities ADD CONSTRAINT billing_document_identity_values_check CHECK (direction IN ('receivable','payable') AND revision >= 1 AND due_on >= issued_on)");
            DB::statement("ALTER TABLE billing_document_commercial_identity_events ADD CONSTRAINT billing_document_identity_event_values_check CHECK (event_type IN ('created','revised','approved','cancelled') AND revision >= 1)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_commercial_identity_events');
        Schema::dropIfExists('billing_document_commercial_identities');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE billing_documents DROP CONSTRAINT billing_documents_values_check');
            DB::statement("ALTER TABLE billing_documents ADD CONSTRAINT billing_documents_values_check CHECK (document_type IN ('customer_invoice','external_carrier_settlement','driver_remuneration') AND status IN ('draft','under_review','approved','closed','cancelled') AND vat_treatment IN ('standard','not_applicable') AND vat_status_snapshot IN ('payer','non_payer') AND period_until >= period_from AND net_amount >= 0 AND vat_amount >= 0 AND gross_amount = net_amount + vat_amount AND ((vat_treatment = 'standard' AND vat_status_snapshot = 'payer' AND vat_rate IS NOT NULL) OR (vat_treatment = 'not_applicable' AND vat_status_snapshot = 'non_payer' AND vat_rate IS NULL AND vat_amount = 0)) AND ((document_type IN ('customer_invoice','external_carrier_settlement') AND counterparty_organization_id IS NOT NULL AND driver_id IS NULL) OR (document_type = 'driver_remuneration' AND counterparty_organization_id IS NULL AND driver_id IS NOT NULL AND vat_treatment = 'not_applicable')))");
        }
    }
};
