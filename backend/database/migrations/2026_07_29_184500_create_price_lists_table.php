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
        Schema::create('price_lists', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('organization_relationship_id')
                ->constrained('organization_relationships')
                ->restrictOnDelete();

            $table->foreignId('owner_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('customer_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('provider_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->char('currency', 3);
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('current_version')->default(1);

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(
                [
                    'organization_relationship_id',
                    'status',
                ],
                'price_lists_relationship_status_index',
            );

            $table->index(
                [
                    'owner_organization_id',
                    'status',
                ],
                'price_lists_owner_status_index',
            );

            $table->index(
                [
                    'customer_organization_id',
                    'provider_organization_id',
                ],
                'price_lists_customer_provider_index',
            );
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
ALTER TABLE price_lists
ADD CONSTRAINT price_lists_status_check
CHECK (
    status IN (
        'draft',
        'active',
        'archived'
    )
)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_lists
ADD CONSTRAINT price_lists_distinct_parties_check
CHECK (customer_organization_id <> provider_organization_id)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_lists
ADD CONSTRAINT price_lists_current_version_check
CHECK (current_version >= 1)
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_lists
ADD CONSTRAINT price_lists_currency_check
CHECK (currency ~ '^[A-Z]{3}$')
SQL);

            DB::statement(<<<'SQL'
ALTER TABLE price_lists
ADD CONSTRAINT price_lists_name_check
CHECK (
    name <> ''
    AND name = btrim(name)
)
SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
