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
        Schema::create('price_list_versions', static function (Blueprint $table): void {
            $table->id();

            $table->foreignId('price_list_id')
                ->constrained('price_lists')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');
            $table->string('status', 32)->default('draft');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('change_reason')->nullable();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                [
                    'price_list_id',
                    'version_number',
                ],
                'price_list_versions_list_version_unique',
            );

            $table->index(
                [
                    'price_list_id',
                    'status',
                ],
                'price_list_versions_list_status_index',
            );

            $table->index(
                [
                    'price_list_id',
                    'valid_from',
                    'valid_until',
                ],
                'price_list_versions_list_validity_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_number_check
CHECK (version_number >= 1)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_status_check
CHECK (
    status IN (
        'draft',
        'approved',
        'active',
        'replaced',
        'expired'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_validity_check
CHECK (
    valid_until IS NULL
    OR valid_from IS NULL
    OR valid_until >= valid_from
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_approval_check
CHECK (
    status = 'draft'
    OR (
        approved_by_user_id IS NOT NULL
        AND approved_at IS NOT NULL
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_list_versions
ADD CONSTRAINT price_list_versions_activation_check
CHECK (
    status NOT IN ('active', 'replaced', 'expired')
    OR (
        valid_from IS NOT NULL
        AND activated_at IS NOT NULL
    )
)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_versions');
    }
};
