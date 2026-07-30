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
        Schema::create('financial_calculations', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('organization_relationship_id')
                ->constrained('organization_relationships')
                ->restrictOnDelete();

            $table->foreignId('price_list_id')
                ->constrained('price_lists')
                ->restrictOnDelete();

            $table->foreignId('price_list_version_id')
                ->constrained('price_list_versions')
                ->restrictOnDelete();

            $table->foreignId('daily_report_id')
                ->constrained('daily_reports')
                ->restrictOnDelete();

            $table->unsignedInteger('daily_report_version');
            $table->unsignedInteger('calculation_version')->default(1);
            $table->string('status', 32)->default('calculated');
            $table->char('currency', 3);
            $table->jsonb('input_snapshot');
            $table->decimal('subtotal_amount', 16, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);

            $table->foreignId('calculated_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('calculated_at');

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('supersedes_calculation_id')
                ->nullable()
                ->constrained('financial_calculations')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'daily_report_id',
                    'daily_report_version',
                    'price_list_version_id',
                    'calculation_version',
                ],
                'fin_calcs_source_version_unique',
            );

            $table->index(
                [
                    'organization_id',
                    'status',
                ],
                'fin_calcs_org_status_index',
            );

            $table->index(
                [
                    'organization_relationship_id',
                    'status',
                ],
                'fin_calcs_relationship_status_index',
            );

            $table->index(
                [
                    'daily_report_id',
                    'daily_report_version',
                ],
                'fin_calcs_report_version_index',
            );

            $table->index(
                [
                    'price_list_id',
                    'price_list_version_id',
                ],
                'fin_calcs_price_list_version_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_status_check
CHECK (
    status IN (
        'calculated',
        'under_review',
        'approved',
        'closed',
        'cancelled'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_report_version_check
CHECK (daily_report_version >= 1)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_calculation_version_check
CHECK (calculation_version >= 1)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_amounts_check
CHECK (
    subtotal_amount >= 0
    AND total_amount >= 0
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_approval_check
CHECK (
    status NOT IN ('approved', 'closed')
    OR (
        approved_by_user_id IS NOT NULL
        AND approved_at IS NOT NULL
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_closed_check
CHECK (
    status <> 'closed'
    OR closed_at IS NOT NULL
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE financial_calculations
ADD CONSTRAINT fin_calcs_supersedes_self_check
CHECK (
    supersedes_calculation_id IS NULL
    OR supersedes_calculation_id <> id
)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_calculations');
    }
};
