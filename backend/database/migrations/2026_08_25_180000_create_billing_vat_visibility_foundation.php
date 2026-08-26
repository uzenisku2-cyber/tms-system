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
        Schema::create('organization_tax_profiles', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->string('vat_status', 32);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('source', 32)->default('manual');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'valid_from'],
                'org_tax_profiles_org_from_unique',
            );
            $table->index(
                ['organization_id', 'valid_from', 'valid_until'],
                'org_tax_profiles_period_index',
            );
        });

        Schema::create('billing_documents', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->foreignId('counterparty_organization_id')
                ->nullable()
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->foreignId('driver_id')
                ->nullable()
                ->constrained('drivers')
                ->restrictOnDelete();
            $table->string('document_type', 40);
            $table->date('period_from');
            $table->date('period_until');
            $table->char('currency', 3)->default('CZK');
            $table->string('vat_treatment', 32);
            $table->string('vat_status_snapshot', 32);
            $table->decimal('net_amount', 16, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->decimal('vat_amount', 16, 2)->default(0);
            $table->decimal('gross_amount', 16, 2)->default(0);
            $table->string('status', 32)->default('draft');
            $table->jsonb('source_snapshot');
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['owner_organization_id', 'period_from', 'period_until'],
                'billing_documents_owner_period_index',
            );
            $table->index(
                ['counterparty_organization_id', 'period_from'],
                'billing_documents_counterparty_period_index',
            );
            $table->index(
                ['driver_id', 'period_from'],
                'billing_documents_driver_period_index',
            );
        });

        Schema::create('billing_document_lines', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_document_id')
                ->constrained('billing_documents')
                ->cascadeOnDelete();
            $table->foreignId('financial_calculation_id')
                ->nullable()
                ->constrained('financial_calculations')
                ->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 14, 3)->default(1);
            $table->decimal('unit_rate', 14, 4)->default(0);
            $table->decimal('net_amount', 16, 2)->default(0);
            $table->decimal('vat_amount', 16, 2)->default(0);
            $table->decimal('gross_amount', 16, 2)->default(0);
            $table->unsignedSmallInteger('position');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['billing_document_id', 'position'],
                'billing_document_lines_position_unique',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresConstraints();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_document_lines');
        Schema::dropIfExists('billing_documents');
        Schema::dropIfExists('organization_tax_profiles');
    }

    private function addPostgresConstraints(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE organization_tax_profiles
ADD CONSTRAINT org_tax_profiles_values_check
CHECK (
    vat_status IN ('payer', 'non_payer')
    AND (valid_until IS NULL OR valid_until >= valid_from)
    AND (
        (vat_status = 'payer' AND vat_rate IS NOT NULL AND vat_rate BETWEEN 0 AND 100)
        OR (vat_status = 'non_payer' AND vat_rate IS NULL)
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE billing_documents
ADD CONSTRAINT billing_documents_values_check
CHECK (
    document_type IN ('customer_invoice', 'external_carrier_settlement', 'driver_remuneration')
    AND status IN ('draft', 'under_review', 'approved', 'closed', 'cancelled')
    AND vat_treatment IN ('standard', 'not_applicable')
    AND vat_status_snapshot IN ('payer', 'non_payer')
    AND period_until >= period_from
    AND net_amount >= 0
    AND vat_amount >= 0
    AND gross_amount = net_amount + vat_amount
    AND (
        (vat_treatment = 'standard' AND vat_status_snapshot = 'payer' AND vat_rate IS NOT NULL)
        OR (vat_treatment = 'not_applicable' AND vat_status_snapshot = 'non_payer' AND vat_rate IS NULL AND vat_amount = 0)
    )
    AND (
        (document_type IN ('customer_invoice', 'external_carrier_settlement') AND counterparty_organization_id IS NOT NULL AND driver_id IS NULL)
        OR (document_type = 'driver_remuneration' AND counterparty_organization_id IS NULL AND driver_id IS NOT NULL AND vat_treatment = 'not_applicable')
    )
)
SQL);

        DB::statement(<<<'SQL'
ALTER TABLE billing_document_lines
ADD CONSTRAINT billing_document_lines_values_check
CHECK (
    quantity >= 0
    AND unit_rate >= 0
    AND net_amount >= 0
    AND vat_amount >= 0
    AND gross_amount = net_amount + vat_amount
    AND position >= 1
)
SQL);
    }
};
